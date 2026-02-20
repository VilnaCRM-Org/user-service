<?php

declare(strict_types=1);

namespace App\User\Application\Service;

interface RefreshTokenEventPublisherInterface
{
    public function publishRotated(string $sessionId, string $userId): void;

    public function publishTheftDetected(
        string $sessionId,
        string $userId,
        string $ipAddress,
        string $reason
    ): void;
}
