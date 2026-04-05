<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;

interface RefreshTokenEventFactoryInterface
{
    public function createRotated(
        string $sessionId,
        string $userId,
        string $eventId
    ): RefreshTokenRotatedEvent;

    public function createTheftDetected(
        string $sessionId,
        string $userId,
        string $ipAddress,
        string $reason,
        string $eventId
    ): RefreshTokenTheftDetectedEvent;
}
