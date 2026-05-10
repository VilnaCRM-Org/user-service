<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\EmailChangedEvent;

interface EmailChangedEventFactoryInterface
{
    public function create(
        UserInterface $user,
        string $oldEmail,
        string $eventId
    ): EmailChangedEvent;
}
