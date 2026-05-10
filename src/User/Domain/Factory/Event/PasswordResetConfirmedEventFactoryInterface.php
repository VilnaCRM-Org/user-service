<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\PasswordResetConfirmedEvent;

interface PasswordResetConfirmedEventFactoryInterface
{
    public function create(
        string $userId,
        string $eventId
    ): PasswordResetConfirmedEvent;
}
