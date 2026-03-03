<?php

declare(strict_types=1);

namespace App\User\Application\Component;

interface TwoFactorEventsInterface
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

    public function publishRecoveryCodeUsed(string $userId, int $remainingCount): void;
}
