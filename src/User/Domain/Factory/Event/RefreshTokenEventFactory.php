<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;

final class RefreshTokenEventFactory implements RefreshTokenEventFactoryInterface
{
    #[\Override]
    public function createRotated(
        string $sessionId,
        string $userId,
        string $eventId
    ): RefreshTokenRotatedEvent {
        return new RefreshTokenRotatedEvent($sessionId, $userId, $eventId);
    }

    #[\Override]
    public function createTheftDetected(
        string $sessionId,
        string $userId,
        string $ipAddress,
        string $reason,
        string $eventId
    ): RefreshTokenTheftDetectedEvent {
        return new RefreshTokenTheftDetectedEvent(
            $sessionId,
            $userId,
            $ipAddress,
            $reason,
            $eventId
        );
    }
}
