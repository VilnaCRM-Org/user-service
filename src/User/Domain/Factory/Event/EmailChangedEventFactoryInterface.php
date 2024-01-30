<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\User;
use App\User\Domain\Event\EmailChangedEvent;

interface EmailChangedEventFactoryInterface
{
    public function create(User $user, string $eventId): EmailChangedEvent;
}
