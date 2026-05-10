<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

interface RefreshTokenPublisherInterface
{
    public function publishTokenRotated(string $sessionId, string $userId): void;

    public function publishTheftDetected(
        string $sessionId,
        string $userId,
        string $ipAddress,
        string $reason
    ): void;
}
