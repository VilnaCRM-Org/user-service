<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserRegisteredEvent;

final class UserRegisteredEventFactory implements
    UserRegisteredEventFactoryInterface
{
    #[\Override]
    public function create(
        UserInterface $user,
        string $eventId
    ): UserRegisteredEvent {
        return new UserRegisteredEvent($user->getId(), $user->getEmail(), $eventId);
    }
}
