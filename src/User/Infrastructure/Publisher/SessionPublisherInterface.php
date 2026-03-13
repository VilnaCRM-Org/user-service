<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

interface SessionPublisherInterface
{
    public function publishSessionRevoked(
        string $userId,
        string $sessionId,
        string $reason
    ): void;

    public function publishAllSessionsRevoked(
        string $userId,
        string $reason,
        int $revokedCount
    ): void;
}
