<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;

final readonly class PasswordChangeSessionRevoker
{
    public function __construct(
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository
    ) {
    }

    /**
     * @psalm-return int<0, max>
     */
    public function revokeOtherSessions(
        string $userId,
        string $currentSessionId
    ): int {
        $sessions = $this->authSessionRepository->findByUserId($userId);
        $revokedCount = 0;

        foreach ($sessions as $session) {
            if ($session->isRevoked() || $session->getId() === $currentSessionId) {
                continue;
            }

            $session->revoke();
            $this->authSessionRepository->save($session);
            $this->revokeRefreshTokensForSession($session->getId());
            $revokedCount++;
        }

        return $revokedCount;
    }

    private function revokeRefreshTokensForSession(string $sessionId): void
    {
        $refreshTokens = $this->authRefreshTokenRepository->findBySessionId(
            $sessionId
        );

        foreach ($refreshTokens as $refreshToken) {
            if ($refreshToken->isRevoked()) {
                continue;
            }

            $refreshToken->revoke();
            $this->authRefreshTokenRepository->save($refreshToken);
        }
    }
}
