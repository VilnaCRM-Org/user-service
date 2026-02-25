<?php

declare(strict_types=1);

namespace App\User\Application\Component;

interface RefreshTokenEventsInterface
{
    public function publishRotated(string $sessionId, string $userId): void;

    public function publishTheftDetected(
        string $sessionId,
        string $userId,
        string $ipAddress,
        string $reason
    ): void;
}
