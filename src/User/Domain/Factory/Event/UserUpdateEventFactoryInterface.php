<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserUpdatedEvent;

interface UserUpdateEventFactoryInterface
{
    public function createEmailChanged(
        UserInterface $user,
        string $oldEmail,
        string $eventId
    ): EmailChangedEvent;

    public function createPasswordChanged(
        string $email,
        string $eventId
    ): PasswordChangedEvent;

    public function createUserUpdated(
        UserInterface $user,
        ?string $previousEmail,
        string $eventId
    ): UserUpdatedEvent;
}
