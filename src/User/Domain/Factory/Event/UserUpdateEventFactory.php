<?php

declare(strict_types=1);

namespace App\User\Domain\Factory\Event;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserUpdatedEvent;

final readonly class UserUpdateEventFactory implements UserUpdateEventFactoryInterface
{
    public function __construct(
        private EmailChangedEventFactoryInterface $emailChangedEventFactory,
        private PasswordChangedEventFactoryInterface $passwordChangedEventFactory,
        private UserUpdatedEventFactoryInterface $userUpdatedEventFactory,
    ) {
    }

    #[\Override]
    public function createEmailChanged(
        UserInterface $user,
        string $oldEmail,
        string $eventId
    ): EmailChangedEvent {
        return $this->emailChangedEventFactory->create($user, $oldEmail, $eventId);
    }

    #[\Override]
    public function createPasswordChanged(
        string $email,
        string $eventId
    ): PasswordChangedEvent {
        return $this->passwordChangedEventFactory->create($email, $eventId);
    }

    #[\Override]
    public function createUserUpdated(
        UserInterface $user,
        ?string $previousEmail,
        string $eventId
    ): UserUpdatedEvent {
        return $this->userUpdatedEventFactory->create($user, $previousEmail, $eventId);
    }
}
