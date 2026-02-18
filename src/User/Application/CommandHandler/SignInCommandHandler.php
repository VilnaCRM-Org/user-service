<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\Command\SignInCommandResponse;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\AccountLockoutServiceInterface;
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
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class SignInCommandHandler implements CommandHandlerInterface
{
    private const LOCKOUT_RETRY_AFTER_SECONDS = AccountLockoutServiceInterface::LOCKOUT_SECONDS;
    private const STANDARD_SESSION_TTL_SECONDS = 900;
    private const REMEMBER_ME_SESSION_TTL_SECONDS = 2592000;
    private const LOCKOUT_MESSAGE = 'Account temporarily locked';

    private const DUMMY_PASSWORD = 'signin-dummy-password';
    private string $dummyPasswordHash;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private AccountLockoutServiceInterface $lockoutService,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private AuthTokenFactoryInterface $authTokenFactory,
        private EventBusInterface $eventBus,
        private UlidFactory $ulidFactory,
        private int $pendingTwoFactorTtlSeconds = 300,
        ?string $dummyPasswordHash = null,
    ) {
        $this->dummyPasswordHash = $this->resolveDummyPasswordHash($dummyPasswordHash);
    }

    public function __invoke(SignInCommand $command): void
    {
        $email = $this->normalizeEmail($command->email);
        $this->assertNotLocked($email);

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

    private function assertNotLocked(string $email): void
    {
        if (!$this->lockoutService->isLocked($email)) {
            return;
        }

        $this->publishAccountLockedOutEvent($email);

        throw $this->lockedException();
    }

    private function handleTwoFactorPath(
        User $user,
        SignInCommand $command
    ): void {
        $createdAt = new DateTimeImmutable();
        $pendingTwoFactor = $this->createPendingTwoFactor(
            $user,
            $createdAt,
            $command->rememberMe
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

        $refreshTokenValue = $this->authTokenFactory->generateOpaqueToken();
        $refreshToken = $this->authTokenFactory->createRefreshToken(
            $session->getId(),
            $refreshTokenValue,
            $issuedAt
        );
        $this->authRefreshTokenRepository->save($refreshToken);

        $accessToken = $this->accessTokenGenerator->generate(
            $this->authTokenFactory->buildJwtPayload($user, $session->getId(), $issuedAt)
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
                $this->authTokenFactory->nextEventId()
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
                $this->authTokenFactory->nextEventId()
            )
        );

        if ($lockedAfterFailure) {
            $this->publishAccountLockedOutEvent($email);

            throw $this->lockedException();
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
    }

    private function publishAccountLockedOutEvent(string $email): void
    {
        // AC: NFR-33 - Audit logging with lockout details
        $failedAttempts = AccountLockoutServiceInterface::MAX_ATTEMPTS;
        $lockoutDurationSeconds = AccountLockoutServiceInterface::LOCKOUT_SECONDS;

        $this->eventBus->publish(
            new AccountLockedOutEvent(
                $email,
                $failedAttempts,
                $lockoutDurationSeconds,
                $this->authTokenFactory->nextEventId()
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

    private function resolveDummyPasswordHash(?string $dummyPasswordHash): string
    {
        if (is_string($dummyPasswordHash) && $dummyPasswordHash !== '') {
            return $dummyPasswordHash;
        }

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        return $hasher->hash(self::DUMMY_PASSWORD);
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

    private function createPendingTwoFactor(
        User $user,
        DateTimeImmutable $createdAt,
        bool $rememberMe
    ): PendingTwoFactor {
        return new PendingTwoFactor(
            (string) $this->ulidFactory->create(),
            $user->getId(),
            $createdAt,
            $createdAt->modify(sprintf('+%d seconds', $this->pendingTwoFactorTtlSeconds)),
            $rememberMe
        );
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
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
}
