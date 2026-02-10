<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\Command\CompleteTwoFactorCommandResponse;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class CompleteTwoFactorCommandHandler implements CommandHandlerInterface
{
    private const ACCESS_TOKEN_TTL_SECONDS = 900;
    private const SESSION_TTL_SECONDS = 900;
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';

    private DateInterval $refreshTokenTtl;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private TOTPVerifierInterface $totpVerifier,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UlidFactory $ulidFactory,
        string $refreshTokenTtlSpec = 'P1M',
    ) {
        $this->refreshTokenTtl = new DateInterval($refreshTokenTtlSpec);
    }

    public function __invoke(CompleteTwoFactorCommand $command): void
    {
        $pendingSession = $this->resolvePendingSession($command->pendingSessionId);
        $user = $this->resolveUser($pendingSession->getUserId());
        $verificationMethod = $this->resolveVerificationMethod(
            $user,
            $command->twoFactorCode
        );

        if ($verificationMethod === null) {
            $this->handleTwoFactorFailure($command);
        }

        $issuedAt = new DateTimeImmutable();
        $session = $this->createSession($user, $command, $issuedAt);
        $this->authSessionRepository->save($session);

        $refreshTokenValue = $this->generateOpaqueToken();
        $refreshToken = $this->createRefreshToken($session->getId(), $refreshTokenValue, $issuedAt);
        $this->authRefreshTokenRepository->save($refreshToken);

        $accessToken = $this->accessTokenGenerator->generate(
            $this->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        $this->pendingTwoFactorRepository->delete($pendingSession);

        $command->setResponse(
            new CompleteTwoFactorCommandResponse(
                $accessToken,
                $refreshTokenValue
            )
        );

        $this->eventBus->publish(
            new TwoFactorCompletedEvent(
                $user->getId(),
                $session->getId(),
                $command->ipAddress,
                $command->userAgent,
                $verificationMethod,
                $this->nextEventId()
            )
        );
    }

    private function resolvePendingSession(string $pendingSessionId): PendingTwoFactor
    {
        $pendingSession = $this->pendingTwoFactorRepository->findById($pendingSessionId);
        if (!$pendingSession instanceof PendingTwoFactor || $pendingSession->isExpired()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired two-factor session.');
        }

        return $pendingSession;
    }

    private function resolveUser(string $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User || !$user->isTwoFactorEnabled()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired two-factor session.');
        }

        return $user;
    }

    private function resolveVerificationMethod(
        User $user,
        string $twoFactorCode
    ): ?string {
        if ($this->isTotpCode($twoFactorCode)) {
            return $this->verifyTotp($user, $twoFactorCode)
                ? 'totp'
                : null;
        }

        if (!$this->isRecoveryCode($twoFactorCode)) {
            return null;
        }

        return $this->consumeRecoveryCode($user->getId(), $twoFactorCode)
            ? 'recovery_code'
            : null;
    }

    private function verifyTotp(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        if ($secret === null) {
            return false;
        }

        return $this->totpVerifier->verify($secret, $code);
    }

    private function consumeRecoveryCode(
        string $userId,
        string $plainCode
    ): bool {
        $recoveryCode = $this->findUnusedRecoveryCode($userId, $plainCode);
        if (!$recoveryCode instanceof RecoveryCode) {
            return false;
        }

        $recoveryCode->markAsUsed();
        $this->recoveryCodeRepository->save($recoveryCode);

        return true;
    }

    private function findUnusedRecoveryCode(
        string $userId,
        string $plainCode
    ): ?RecoveryCode {
        foreach ($this->recoveryCodeRepository->findByUserId($userId) as $recoveryCode) {
            if ($recoveryCode->isUsed()) {
                continue;
            }

            if ($recoveryCode->matchesCode($plainCode)) {
                return $recoveryCode;
            }
        }

        return null;
    }

    private function handleTwoFactorFailure(CompleteTwoFactorCommand $command): never
    {
        $this->eventBus->publish(
            new TwoFactorFailedEvent(
                $command->pendingSessionId,
                $command->ipAddress,
                'invalid_code',
                $this->nextEventId()
            )
        );

        throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
    }

    private function createSession(
        User $user,
        CompleteTwoFactorCommand $command,
        DateTimeImmutable $issuedAt
    ): AuthSession {
        return new AuthSession(
            (string) $this->ulidFactory->create(),
            $user->getId(),
            $command->ipAddress,
            $command->userAgent,
            $issuedAt,
            $issuedAt->modify(sprintf('+%d seconds', self::SESSION_TTL_SECONDS)),
            false
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

    /**
     * @return array<string, int|string|array<string>>
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

    private function isTotpCode(string $code): bool
    {
        return preg_match('/^\d{6}$/', $code) === 1;
    }

    private function isRecoveryCode(string $code): bool
    {
        return preg_match('/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/', $code) === 1;
    }

    private function generateOpaqueToken(): string
    {
        return rtrim(
            strtr(base64_encode(random_bytes(32)), '+/', '-_'),
            '='
        );
    }

    private function nextEventId(): string
    {
        return (string) $this->uuidFactory->create();
    }
}
