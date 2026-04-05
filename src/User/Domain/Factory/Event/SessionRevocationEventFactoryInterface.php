<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;

interface SessionRevocationEventFactoryInterface
{
    public function createSessionRevoked(
        string $userId,
        string $sessionId,
        string $reason,
        string $eventId
    ): SessionRevokedEvent;

    public function createAllSessionsRevoked(
        string $userId,
        string $reason,
        int $revokedCount,
        string $eventId
    ): AllSessionsRevokedEvent;
}
