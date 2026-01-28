<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserDeletedEvent;

final class UserDeletedEventFactory implements UserDeletedEventFactoryInterface
{
    #[\Override]
    public function create(UserInterface $user, string $eventId): UserDeletedEvent
    {
        return new UserDeletedEvent(
            $user->getId(),
            $user->getEmail(),
            $eventId
        );
    }
}
