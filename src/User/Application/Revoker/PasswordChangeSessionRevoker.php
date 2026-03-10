<?php

declare(strict_types=1);

namespace App\User\Application\Revoker;

use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;

/**
 * @psalm-api
 */
final readonly class PasswordChangeSessionRevoker implements PasswordChangeSessionRevokerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
    ) {
    }

    #[\Override]
    public function revokeOtherSessions(string $userId, string $currentSessionId): int
    {
        $sessions = $this->authSessionRepository->findByUserId($userId);
        $revokedCount = 0;

        foreach ($sessions as $session) {
            if ($session->isRevoked() || $session->getId() === $currentSessionId) {
                continue;
            }

            $session->revoke();
            $this->authSessionRepository->save($session);
            $this->revokeRefreshTokensForSession($session->getId());
            ++$revokedCount;
        }

        return $revokedCount;
    }

    private function revokeRefreshTokensForSession(string $sessionId): void
    {
        foreach ($this->authRefreshTokenRepository->findBySessionId($sessionId) as $refreshToken) {
            if ($refreshToken->isRevoked()) {
                continue;
            }

            $refreshToken->revoke();
            $this->authRefreshTokenRepository->save($refreshToken);
        }
    }
}
