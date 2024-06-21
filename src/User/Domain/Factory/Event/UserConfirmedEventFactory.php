<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\UserConfirmedEvent;

final class UserConfirmedEventFactory implements
    UserConfirmedEventFactoryInterface
{
    public function create(
        ConfirmationToken $token,
        string $eventId
    ): UserConfirmedEvent {
        return new UserConfirmedEvent($token, $eventId);
    }
}
