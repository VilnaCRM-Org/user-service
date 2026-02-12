<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\Command\SignInCommandResponse;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\AccountLockoutServiceInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class SignInCommandHandler implements CommandHandlerInterface
{
    private const ACCESS_TOKEN_TTL_SECONDS = 900;
    private const LOCKOUT_RETRY_AFTER_SECONDS = 900;
    private const STANDARD_SESSION_TTL_SECONDS = 900;
    private const REMEMBER_ME_SESSION_TTL_SECONDS = 2592000;
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const LOCKOUT_MESSAGE = 'Account temporarily locked';

    private const DUMMY_PASSWORD = 'signin-dummy-password';
    private const DUMMY_BCRYPT_COST = 4;

    private string $dummyPasswordHash;
    private DateInterval $refreshTokenTtl;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private AccountLockoutServiceInterface $lockoutService,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UlidFactory $ulidFactory,
        private int $pendingTwoFactorTtlSeconds = 300,
        string $refreshTokenTtlSpec = 'P1M',
    ) {
        $this->dummyPasswordHash = password_hash(
            self::DUMMY_PASSWORD,
            PASSWORD_BCRYPT,
            ['cost' => self::DUMMY_BCRYPT_COST]
        );

        $this->refreshTokenTtl = new DateInterval($refreshTokenTtlSpec);
    }

    public function __invoke(SignInCommand $command): void
    {
        $email = $this->normalizeEmail($command->email);
        $this->assertNotLocked($email, $command);

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $user = $this->resolveUser($email);

        if (!$this->credentialsAreValid($hasher, $user, $command->password)) {
            $this->handleAuthenticationFailure($email, $command);
        }

        $this->lockoutService->clearFailures($email);

        if ($user->isTwoFactorEnabled()) {
            $this->handleTwoFactorPath($user, $command);

            return;
        }

        $this->handleDirectSignIn($user, $command);
    }

    private function assertNotLocked(
        string $email,
        SignInCommand $command
    ): void {
        if (!$this->lockoutService->isLocked($email)) {
            return;
        }

        $this->publishAccountLockedOutEvent(
            $email,
            $command->ipAddress,
            $command->userAgent
        );

        throw $this->lockedException();
    }

    private function handleTwoFactorPath(
        User $user,
        SignInCommand $command
    ): void {
        $createdAt = new DateTimeImmutable();
        $pendingTwoFactor = $this->createPendingTwoFactor(
            $user,
            $createdAt
        );
        $this->pendingTwoFactorRepository->save($pendingTwoFactor);

        $command->setResponse(
            new SignInCommandResponse(
                true,
                null,
                null,
                $pendingTwoFactor->getId()
            )
        );
    }

    private function handleDirectSignIn(
        User $user,
        SignInCommand $command
    ): void {
        $issuedAt = new DateTimeImmutable();
        $session = $this->createAuthSession($user, $command, $issuedAt);
        $this->authSessionRepository->save($session);

        $refreshTokenValue = $this->generateOpaqueToken();
        $refreshToken = $this->createRefreshToken(
            $session->getId(),
            $refreshTokenValue,
            $issuedAt
        );
        $this->authRefreshTokenRepository->save($refreshToken);

        $accessToken = $this->accessTokenGenerator->generate(
            $this->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        $command->setResponse(new SignInCommandResponse(
            false,
            $accessToken,
            $refreshTokenValue
        ));

        $this->publishSignedInEvent($user, $session, $command);
    }

    private function publishSignedInEvent(
        User $user,
        AuthSession $session,
        SignInCommand $command
    ): void {
        $this->eventBus->publish(
            new UserSignedInEvent(
                $user->getId(),
                $user->getEmail(),
                $session->getId(),
                $command->ipAddress,
                $command->userAgent,
                false,  // AC: NFR-33 - twoFactorUsed is false during password auth (step 1)
                $this->nextEventId()
            )
        );
    }

    private function handleAuthenticationFailure(
        string $email,
        SignInCommand $command
    ): never {
        $lockedAfterFailure = $this->lockoutService->recordFailure($email);

        $this->eventBus->publish(
            new SignInFailedEvent(
                $email,
                $command->ipAddress,
                $command->userAgent,
                'invalid_credentials',  // AC: NFR-33 - reason for audit logging
                $this->nextEventId()
            )
        );

        if ($lockedAfterFailure) {
            $this->publishAccountLockedOutEvent(
                $email,
                $command->ipAddress,
                $command->userAgent
            );

            throw $this->lockedException();
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
    }

    private function publishAccountLockedOutEvent(
        string $email,
        string $ipAddress,
        string $userAgent
    ): void {
        // AC: NFR-33 - Audit logging with lockout details
        // TODO: Get actual values from lockout service configuration
        $failedAttempts = 5;  // Default threshold
        $lockoutDurationSeconds = 900;  // 15 minutes

        $this->eventBus->publish(
            new AccountLockedOutEvent(
                $email,
                $failedAttempts,
                $lockoutDurationSeconds,
                $this->nextEventId()
            )
        );
    }

    private function credentialsAreValid(
        PasswordHasherInterface $hasher,
        ?User $user,
        string $password
    ): bool {
        if (!$user instanceof User) {
            $hasher->verify($this->dummyPasswordHash, $password);

            return false;
        }

        return $hasher->verify($user->getPassword(), $password);
    }

    private function resolveUser(string $email): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        return $user instanceof User ? $user : null;
    }

    private function createAuthSession(
        User $user,
        SignInCommand $command,
        DateTimeImmutable $issuedAt
    ): AuthSession {
        $expiresAt = $issuedAt->modify(sprintf(
            '+%d seconds',
            $command->rememberMe
                ? self::REMEMBER_ME_SESSION_TTL_SECONDS
                : self::STANDARD_SESSION_TTL_SECONDS
        ));

        return new AuthSession(
            (string) $this->ulidFactory->create(),
            $user->getId(),
            $command->ipAddress,
            $command->userAgent,
            $issuedAt,
            $expiresAt,
            $command->rememberMe
        );
    }

    private function createRefreshToken(
        string $sessionId,
        string $plainToken,
        DateTimeImmutable $issuedAt
    ): AuthRefreshToken {
        return new AuthRefreshToken(
            (string) $this->ulidFactory->create(),
            $sessionId,
            $plainToken,
            $issuedAt->add($this->refreshTokenTtl)
        );
    }

    private function createPendingTwoFactor(
        User $user,
        DateTimeImmutable $createdAt
    ): PendingTwoFactor {
        return new PendingTwoFactor(
            (string) $this->ulidFactory->create(),
            $user->getId(),
            $createdAt,
            $createdAt->modify(sprintf('+%d seconds', $this->pendingTwoFactorTtlSeconds))
        );
    }

    /**
     * @return (int|string|string[])[]
     *
     * @psalm-return array{sub: string, iss: 'vilnacrm-user-service', aud: 'vilnacrm-api', exp: int, iat: int, nbf: int, jti: string, sid: string, roles: list{'ROLE_USER'}}
     */
    private function buildJwtPayload(
        User $user,
        string $sessionId,
        DateTimeImmutable $issuedAt
    ): array {
        $issuedAtTimestamp = $issuedAt->getTimestamp();

        return [
            'sub' => $user->getId(),
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'exp' => $issuedAtTimestamp + self::ACCESS_TOKEN_TTL_SECONDS,
            'iat' => $issuedAtTimestamp,
            'nbf' => $issuedAtTimestamp,
            'jti' => (string) $this->uuidFactory->create(),
            'sid' => $sessionId,
            'roles' => ['ROLE_USER'],
        ];
    }

    private function nextEventId(): string
    {
        return (string) $this->uuidFactory->create();
    }

    private function lockedException(): LockedHttpException
    {
        return new LockedHttpException(
            self::LOCKOUT_MESSAGE,
            null,
            0,
            ['Retry-After' => (string) self::LOCKOUT_RETRY_AFTER_SECONDS]
        );
    }

    private function generateOpaqueToken(): string
    {
        return rtrim(
            strtr(base64_encode(random_bytes(32)), '+/', '-_'),
            '='
        );
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
