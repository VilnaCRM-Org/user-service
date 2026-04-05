<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;

interface SignInEventFactoryInterface
{
    public function createSignedIn(
        string $userId,
        string $email,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        bool $twoFactorUsed,
        string $eventId
    ): UserSignedInEvent;

    public function createFailed(
        string $email,
        string $ipAddress,
        string $userAgent,
        string $reason,
        string $eventId
    ): SignInFailedEvent;

    public function createLockedOut(
        string $email,
        int $failedAttempts,
        int $lockoutDurationSeconds,
        string $eventId
    ): AccountLockedOutEvent;
}
