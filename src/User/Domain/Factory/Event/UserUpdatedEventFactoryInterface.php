<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserUpdatedEvent;

interface UserUpdatedEventFactoryInterface
{
    public function create(
        UserInterface $user,
        ?string $previousEmail,
        string $eventId
    ): UserUpdatedEvent;
}
