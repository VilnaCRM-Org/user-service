<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;

final class SessionRevocationEventFactory implements SessionRevocationEventFactoryInterface
{
    #[\Override]
    public function createSessionRevoked(
        string $userId,
        string $sessionId,
        string $reason,
        string $eventId
    ): SessionRevokedEvent {
        return new SessionRevokedEvent($userId, $sessionId, $reason, $eventId);
    }

    #[\Override]
    public function createAllSessionsRevoked(
        string $userId,
        string $reason,
        int $revokedCount,
        string $eventId
    ): AllSessionsRevokedEvent {
        return new AllSessionsRevokedEvent($userId, $reason, $revokedCount, $eventId);
    }
}
