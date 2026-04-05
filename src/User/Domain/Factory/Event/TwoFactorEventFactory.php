<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;

final class TwoFactorEventFactory implements TwoFactorEventFactoryInterface
{
    #[\Override]
    public function createEnabled(
        string $userId,
        string $email,
        string $eventId
    ): TwoFactorEnabledEvent {
        return new TwoFactorEnabledEvent($userId, $email, $eventId);
    }

    #[\Override]
    public function createDisabled(
        string $userId,
        string $email,
        string $eventId
    ): TwoFactorDisabledEvent {
        return new TwoFactorDisabledEvent($userId, $email, $eventId);
    }

    #[\Override]
    public function createCompleted(
        string $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        string $method,
        string $eventId
    ): TwoFactorCompletedEvent {
        return new TwoFactorCompletedEvent(
            $userId,
            $sessionId,
            $ipAddress,
            $userAgent,
            $method,
            $eventId
        );
    }

    #[\Override]
    public function createFailed(
        string $pendingSessionId,
        string $ipAddress,
        string $reason,
        string $eventId
    ): TwoFactorFailedEvent {
        return new TwoFactorFailedEvent(
            $pendingSessionId,
            $ipAddress,
            $reason,
            $eventId
        );
    }

    #[\Override]
    public function createRecoveryCodeUsed(
        string $userId,
        int $remainingCount,
        string $eventId
    ): RecoveryCodeUsedEvent {
        return new RecoveryCodeUsedEvent($userId, $remainingCount, $eventId);
    }
}
