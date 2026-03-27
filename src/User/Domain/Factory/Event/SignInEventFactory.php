<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;

final class SignInEventFactory implements SignInEventFactoryInterface
{
    #[\Override]
    public function createSignedIn(
        string $userId,
        string $email,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        bool $twoFactorUsed,
        string $eventId
    ): UserSignedInEvent {
        return new UserSignedInEvent(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            $twoFactorUsed,
            $eventId
        );
    }

    #[\Override]
    public function createFailed(
        string $email,
        string $ipAddress,
        string $userAgent,
        string $reason,
        string $eventId
    ): SignInFailedEvent {
        return new SignInFailedEvent($email, $ipAddress, $userAgent, $reason, $eventId);
    }

    #[\Override]
    public function createLockedOut(
        string $email,
        int $failedAttempts,
        int $lockoutDurationSeconds,
        string $eventId
    ): AccountLockedOutEvent {
        return new AccountLockedOutEvent(
            $email,
            $failedAttempts,
            $lockoutDurationSeconds,
            $eventId
        );
    }
}
