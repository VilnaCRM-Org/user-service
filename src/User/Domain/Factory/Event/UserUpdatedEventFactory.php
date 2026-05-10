<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserUpdatedEvent;

final class UserUpdatedEventFactory implements UserUpdatedEventFactoryInterface
{
    #[\Override]
    public function create(
        UserInterface $user,
        ?string $previousEmail,
        string $eventId
    ): UserUpdatedEvent {
        return new UserUpdatedEvent(
            $user->getId(),
            $user->getEmail(),
            $previousEmail,
            $eventId
        );
    }
}
