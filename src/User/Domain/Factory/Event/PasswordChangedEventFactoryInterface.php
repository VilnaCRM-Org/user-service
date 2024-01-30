<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\PasswordChangedEvent;

interface PasswordChangedEventFactoryInterface
{
    public function create(
        string $email,
        string $eventId
    ): PasswordChangedEvent;
}
