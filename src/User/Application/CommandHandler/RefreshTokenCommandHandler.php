<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Application\Service\RefreshTokenContextResolver;
use App\User\Application\Service\RefreshTokenIssuer;
use App\User\Application\Service\RefreshTokenTheftDetector;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use DateTimeImmutable;

final readonly class RefreshTokenCommandHandler implements
    CommandHandlerInterface
{
    private const DEFAULT_GRACE_WINDOW_SECONDS = 60;

    public function __construct(
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private RefreshTokenContextResolver $contextResolver,
        private RefreshTokenIssuer $tokenIssuer,
        private RefreshTokenTheftDetector $theftDetector,
        private int $refreshTokenGraceWindowSeconds = self::DEFAULT_GRACE_WINDOW_SECONDS,
    ) {
    }

    public function __invoke(
        RefreshTokenCommand $command
    ): RefreshTokenCommandResponse {
        [$oldToken, $session, $user] = $this->contextResolver->resolve(
            $command->refreshToken
        );
        $currentTime = new DateTimeImmutable();

        $alreadyRotatedResponse = $this->handleIfAlreadyRotated(
            $oldToken,
            $session,
            $user,
            $command,
            $currentTime
        );
        if ($alreadyRotatedResponse instanceof RefreshTokenCommandResponse) {
            return $alreadyRotatedResponse;
        }

        return $this->rotateActiveToken($oldToken, $user, $session, $currentTime);
    }

    private function handleIfAlreadyRotated(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): ?RefreshTokenCommandResponse {
        $rotatedResponse = $this->tryHandleRotatedToken(
            $oldToken,
            $session,
            $user,
            $currentTime
        );
        if ($rotatedResponse instanceof RefreshTokenCommandResponse) {
            return $rotatedResponse;
        }

        return $this->tryHandleConcurrentRotation(
            $oldToken->getTokenHash(),
            $session,
            $user,
            $currentTime,
            $command->refreshToken
        );
    }

    private function handleRotatedToken(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        DateTimeImmutable $currentTime
    ): RefreshTokenCommandResponse {
        if (!$oldToken->isWithinGracePeriod(
            $currentTime,
            $this->refreshTokenGraceWindowSeconds
        )) {
            $this->theftDetector->respondToTheft(
                $oldToken,
                $session,
                $user,
                'grace_period_expired'
            );
        }

        $tokens = $this->refreshTokenRepository->findBySessionId($session->getId());
        return $this->handleGraceWindowReuse(
            $oldToken,
            $session,
            $user,
            $currentTime,
            $tokens
        );
    }

    /**
     * @param list<AuthRefreshToken> $tokens
     */
    private function hasLaterRotation(
        AuthRefreshToken $oldToken,
        array $tokens
    ): bool {
        $oldRotatedAt = $oldToken->getRotatedAt();
        assert($oldRotatedAt instanceof DateTimeImmutable);

        foreach ($tokens as $sessionToken) {
            if ($this->isLaterRotatedToken(
                $sessionToken,
                $oldToken,
                $oldRotatedAt
            )) {
                return true;
            }
        }

        return false;
    }

    private function isLaterRotatedToken(
        AuthRefreshToken $candidateToken,
        AuthRefreshToken $oldToken,
        DateTimeImmutable $oldRotatedAt
    ): bool {
        if ($candidateToken->getId() === $oldToken->getId()) {
            return false;
        }

        if ($candidateToken->isRevoked()) {
            return false;
        }

        $candidateRotatedAt = $candidateToken->getRotatedAt();

        return $candidateRotatedAt instanceof DateTimeImmutable
            && $candidateRotatedAt > $oldRotatedAt;
    }

    /**
     * @param list<AuthRefreshToken> $tokens
     */
    private function handleGraceWindowReuse(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        DateTimeImmutable $currentTime,
        array $tokens
    ): RefreshTokenCommandResponse {
        if ($this->hasLaterRotation($oldToken, $tokens)) {
            $this->theftDetector->respondToTheft(
                $oldToken,
                $session,
                $user,
                'superseded_rotation',
                $tokens
            );
        }

        $this->assertGraceWindowIsEligible($oldToken, $session, $user, $currentTime, $tokens);
        $oldToken->markGraceUsed();

        return $this->tokenIssuer->issueRotatedTokens($user, $session);
    }

    private function tryHandleRotatedToken(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        DateTimeImmutable $currentTime
    ): ?RefreshTokenCommandResponse {
        if (!$oldToken->isRotated()) {
            return null;
        }

        return $this->handleRotatedToken($oldToken, $session, $user, $currentTime);
    }

    private function tryHandleConcurrentRotation(
        string $tokenHash,
        AuthSession $session,
        User $user,
        DateTimeImmutable $currentTime,
        string $plainToken
    ): ?RefreshTokenCommandResponse {
        $markedSuccessfully = $this->refreshTokenRepository->markAsRotatedIfActive(
            $tokenHash,
            $currentTime
        );
        if ($markedSuccessfully) {
            return null;
        }

        $latestToken = $this->contextResolver->resolveRotatedRefreshToken($plainToken);
        return $this->handleRotatedToken(
            $latestToken,
            $session,
            $user,
            $currentTime
        );
    }

    private function rotateActiveToken(
        AuthRefreshToken $oldToken,
        User $user,
        AuthSession $session,
        DateTimeImmutable $currentTime
    ): RefreshTokenCommandResponse {
        $oldToken->markAsRotated($currentTime);

        return $this->tokenIssuer->issueRotatedTokens($user, $session);
    }

    /**
     * @param list<AuthRefreshToken> $tokens
     */
    private function assertGraceWindowIsEligible(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        DateTimeImmutable $currentTime,
        array $tokens
    ): void {
        if ($this->consumeGraceWindow($oldToken, $currentTime)) {
            return;
        }

        $this->theftDetector->respondToTheft(
            $oldToken,
            $session,
            $user,
            'double_grace_use',
            $tokens
        );
    }

    private function consumeGraceWindow(
        AuthRefreshToken $oldToken,
        DateTimeImmutable $currentTime
    ): bool {
        if ($oldToken->isGraceUsed()) {
            return false;
        }

        $graceWindowStartedAt = $currentTime->setTimestamp(
            $currentTime->getTimestamp() - $this->refreshTokenGraceWindowSeconds
        );

        return $this->refreshTokenRepository->markGraceUsedIfEligible(
            $oldToken->getTokenHash(),
            $graceWindowStartedAt,
            $currentTime
        );
    }
}
