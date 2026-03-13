<?php

declare(strict_types=1);

namespace App\User\Application\Processor\Revoker;

use App\User\Application\Processor\EventPublisher\SessionEventsInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;

final readonly class AllSessionsRevoker implements AllSessionsRevokerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $sessionRepository,
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private SessionEventsInterface $sessionEvents,
    ) {
    }

    #[\Override]
    public function revokeAllSessions(string $userId, string $reason): int
    {
        $sessions = $this->sessionRepository->findByUserId($userId);
        $revokedCount = 0;

        foreach ($sessions as $session) {
            $this->refreshTokenRepository->revokeBySessionId($session->getId());

            if ($session->isRevoked()) {
                continue;
            }

            $session->revoke();
            $this->sessionRepository->save($session);
            ++$revokedCount;
        }

        $this->sessionEvents->publishAllSessionsRevoked(
            $userId,
            $reason,
            $revokedCount
        );

        return $revokedCount;
    }
}
