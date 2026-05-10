<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;

interface TwoFactorEventFactoryInterface
{
    public function createEnabled(
        string $userId,
        string $email,
        string $eventId
    ): TwoFactorEnabledEvent;

    public function createDisabled(
        string $userId,
        string $email,
        string $eventId
    ): TwoFactorDisabledEvent;

    public function createCompleted(
        string $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        string $method,
        string $eventId
    ): TwoFactorCompletedEvent;

    public function createFailed(
        string $pendingSessionId,
        string $ipAddress,
        string $reason,
        string $eventId
    ): TwoFactorFailedEvent;

    public function createRecoveryCodeUsed(
        string $userId,
        int $remainingCount,
        string $eventId
    ): RecoveryCodeUsedEvent;
}
