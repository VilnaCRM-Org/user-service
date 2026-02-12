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
use App\User\Domain\Event\RecoveryCodeUsedEvent;
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

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class CompleteTwoFactorCommandHandler implements CommandHandlerInterface
{
    private const ACCESS_TOKEN_TTL_SECONDS = 900;
    private const SESSION_TTL_SECONDS = 900;
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const RECOVERY_CODE_WARNING_THRESHOLD = 2;

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
        $session = $this->createSession($user, $command, $issuedAt);
        $this->authSessionRepository->save($session);

        $tokens = $this->generateTokenPair($user, $session, $issuedAt);
        $remaining = $this->resolveRemainingCodes($user, $method);

        $this->pendingTwoFactorRepository->delete($pendingSession);
        $command->setResponse(
            $this->buildResponse($tokens[0], $tokens[1], $remaining)
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
        $refreshToken = $this->generateOpaqueToken();
        $this->saveRefreshToken($session->getId(), $refreshToken, $issuedAt);

        $accessToken = $this->accessTokenGenerator->generate(
            $this->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        return [$accessToken, $refreshToken];
    }

    /**
     * @psalm-return int<0, max>|null
     */
    private function resolveRemainingCodes(
        User $user,
        ?string $method
    ): int|null {
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
            $this->createRefreshToken($sessionId, $plainToken, $issuedAt)
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
                $this->nextEventId()
            )
        );
    }

    private function buildResponse(
        string $accessToken,
        string $refreshToken,
        ?int $remainingCodes
    ): CompleteTwoFactorCommandResponse {
        if ($remainingCodes === null) {
            return new CompleteTwoFactorCommandResponse($accessToken, $refreshToken);
        }

        if ($remainingCodes > self::RECOVERY_CODE_WARNING_THRESHOLD) {
            return new CompleteTwoFactorCommandResponse($accessToken, $refreshToken);
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
                $this->nextEventId()
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
    ): string|null|null {
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
