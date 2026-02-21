<?php

declare(strict_types=1);

namespace App\User\Application\Service;

interface TwoFactorEventPublisherInterface
{
    public function publishEnabled(string $userId, string $email): void;

    public function publishDisabled(string $userId, string $email): void;

    public function publishCompleted(
        string $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        ?string $verificationMethod
    ): void;

    public function publishFailed(
        string $pendingSessionId,
        string $ipAddress,
        string $reason
    ): void;

    public function publishRecoveryCodeUsed(
        string $userId,
        int $remainingCount
    ): void;

    public function publishAllSessionsRevoked(
        string $userId,
        string $reason,
        int $revokedCount
    ): void;
}
