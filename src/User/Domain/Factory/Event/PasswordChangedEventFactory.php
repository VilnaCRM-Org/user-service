<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Event\PasswordChangedEvent;

final class PasswordChangedEventFactory implements
    PasswordChangedEventFactoryInterface
{
    #[\Override]
    public function create(string $email, string $eventId): PasswordChangedEvent
    {
        return new PasswordChangedEvent($email, $eventId);
    }
}
