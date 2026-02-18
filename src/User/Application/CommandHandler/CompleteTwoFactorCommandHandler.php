<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\Command\CompleteTwoFactorCommandResponse;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class CompleteTwoFactorCommandHandler implements CommandHandlerInterface
{
    private const STANDARD_SESSION_TTL_SECONDS = 900;
    private const REMEMBER_ME_SESSION_TTL_SECONDS = 2592000;
    private const RECOVERY_CODE_WARNING_THRESHOLD = 2;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private TOTPVerifierInterface $totpVerifier,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private AuthTokenFactoryInterface $authTokenFactory,
        private EventBusInterface $eventBus,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function __invoke(CompleteTwoFactorCommand $command): void
    {
        $pendingSession = $this->resolvePendingSession(
            $command->pendingSessionId
        );
        $user = $this->resolveUser($pendingSession->getUserId());
        $method = $this->resolveVerificationMethod(
            $user,
            $command->twoFactorCode
        );

        if ($method === null) {
            $this->handleTwoFactorFailure($command);
        }

        $this->issueTokensAndComplete(
            $user,
            $command,
            $pendingSession,
            $method
        );
    }

    private function issueTokensAndComplete(
        User $user,
        CompleteTwoFactorCommand $command,
        PendingTwoFactor $pendingSession,
        ?string $method
    ): void {
        $issuedAt = new DateTimeImmutable();
        $session = $this->createSession($user, $command, $pendingSession, $issuedAt);
        $this->authSessionRepository->save($session);

        $tokens = $this->generateTokenPair($user, $session, $issuedAt);
        $remaining = $this->resolveRemainingCodes($user, $method);

        $this->pendingTwoFactorRepository->delete($pendingSession);
        $rememberMe = $pendingSession->isRememberMe();
        $command->setResponse(
            $this->buildResponse($tokens[0], $tokens[1], $rememberMe, $remaining)
        );

        $this->publishEvents($user, $session, $command, $method, $remaining);
    }

    /**
     * @return array{string, string}
     */
    private function generateTokenPair(
        User $user,
        AuthSession $session,
        DateTimeImmutable $issuedAt
    ): array {
        $refreshToken = $this->authTokenFactory->generateOpaqueToken();
        $this->saveRefreshToken($session->getId(), $refreshToken, $issuedAt);

        $accessToken = $this->accessTokenGenerator->generate(
            $this->authTokenFactory->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        return [$accessToken, $refreshToken];
    }

    /**
     * @psalm-return int<0, max>|null
     */
    private function resolveRemainingCodes(
        User $user,
        ?string $method
    ): ?int {
        if ($method !== 'recovery_code') {
            return null;
        }

        return $this->countRemainingUnusedCodes($user->getId());
    }

    private function publishEvents(
        User $user,
        AuthSession $session,
        CompleteTwoFactorCommand $command,
        ?string $method,
        ?int $remaining
    ): void {
        if ($remaining !== null) {
            $this->publishRecoveryCodeUsedEvent($user, $remaining);
        }

        $this->publishCompletedEvent($user, $session, $command, $method);
    }

    private function saveRefreshToken(
        string $sessionId,
        string $plainToken,
        DateTimeImmutable $issuedAt
    ): void {
        $this->authRefreshTokenRepository->save(
            $this->authTokenFactory->createRefreshToken($sessionId, $plainToken, $issuedAt)
        );
    }

    private function publishCompletedEvent(
        User $user,
        AuthSession $session,
        CompleteTwoFactorCommand $command,
        ?string $verificationMethod
    ): void {
        $this->eventBus->publish(
            new TwoFactorCompletedEvent(
                $user->getId(),
                $session->getId(),
                $command->ipAddress,
                $command->userAgent,
                $verificationMethod,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    private function buildResponse(
        string $accessToken,
        string $refreshToken,
        bool $rememberMe,
        ?int $remainingCodes
    ): CompleteTwoFactorCommandResponse {
        if ($remainingCodes === null) {
            return new CompleteTwoFactorCommandResponse($accessToken, $refreshToken, $rememberMe);
        }

        if ($remainingCodes > self::RECOVERY_CODE_WARNING_THRESHOLD) {
            return new CompleteTwoFactorCommandResponse($accessToken, $refreshToken, $rememberMe);
        }

        $warningMessage = $remainingCodes === 0
            ? 'All recovery codes have been used. Regenerate immediately.'
            : sprintf(
                'Only %d recovery code(s) remaining. Regenerate soon.',
                $remainingCodes
            );

        return new CompleteTwoFactorCommandResponse(
            $accessToken,
            $refreshToken,
            $rememberMe,
            $remainingCodes,
            $warningMessage
        );
    }

    private function publishRecoveryCodeUsedEvent(
        User $user,
        int $remainingCodes
    ): void {
        $this->eventBus->publish(
            new RecoveryCodeUsedEvent(
                $user->getId(),
                $remainingCodes,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    /**
     * @psalm-return int<0, max>
     */
    private function countRemainingUnusedCodes(string $userId): int
    {
        $count = 0;
        foreach ($this->recoveryCodeRepository->findByUserId($userId) as $code) {
            if (!$code->isUsed()) {
                ++$count;
            }
        }

        return $count;
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
                $this->authTokenFactory->nextEventId()
            )
        );

        throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
    }

    private function createSession(
        User $user,
        CompleteTwoFactorCommand $command,
        PendingTwoFactor $pendingSession,
        DateTimeImmutable $issuedAt
    ): AuthSession {
        $rememberMe = $pendingSession->isRememberMe();
        $ttlSeconds = $rememberMe
            ? self::REMEMBER_ME_SESSION_TTL_SECONDS
            : self::STANDARD_SESSION_TTL_SECONDS;

        return new AuthSession(
            (string) $this->ulidFactory->create(),
            $user->getId(),
            $command->ipAddress,
            $command->userAgent,
            $issuedAt,
            $issuedAt->modify(sprintf('+%d seconds', $ttlSeconds)),
            $rememberMe
        );
    }

    private function isTotpCode(string $code): bool
    {
        return preg_match('/^\d{6}$/', $code) === 1;
    }

    private function isRecoveryCode(string $code): bool
    {
        return preg_match('/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/', $code) === 1;
    }
}
