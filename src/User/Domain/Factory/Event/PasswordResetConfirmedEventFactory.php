<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\PasswordResetConfirmedEvent;

final class PasswordResetConfirmedEventFactory implements
    PasswordResetConfirmedEventFactoryInterface
{
    #[\Override]
    public function create(
        string $userId,
        string $eventId
    ): PasswordResetConfirmedEvent {
        return new PasswordResetConfirmedEvent($userId, $eventId);
    }
}
