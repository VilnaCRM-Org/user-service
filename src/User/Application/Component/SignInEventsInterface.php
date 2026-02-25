<?php

declare(strict_types=1);

namespace App\User\Application\Component;

interface SignInEventsInterface
{
    public function publishSignedIn(
        string $userId,
        string $email,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        bool $twoFactorUsed
    ): void;

    public function publishFailed(
        string $email,
        string $ipAddress,
        string $userAgent,
        string $reason
    ): void;

    public function publishLockedOut(
        string $email,
        int $failedAttempts,
        int $lockoutDurationSeconds
    ): void;
}
