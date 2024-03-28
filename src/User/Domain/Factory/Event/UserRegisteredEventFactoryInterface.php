<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserRegisteredEvent;

interface UserRegisteredEventFactoryInterface
{
    public function create(User $user, string $eventId): UserRegisteredEvent;
}
