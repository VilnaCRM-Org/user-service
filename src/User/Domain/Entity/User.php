<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

class User implements UserInterface
{
    private bool $confirmed;

    public function __construct(
        private string $email,
        private string $initials,
        private string $password,
        private UuidInterface $id,
    ) {
        $this->confirmed = false;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function confirm(
        ConfirmationToken $token,
        string $eventID,
        UserConfirmedEventFactoryInterface $userConfirmedEventFactory
    ): UserConfirmedEvent {
        $this->confirmed = true;

        return $userConfirmedEventFactory->create($token, $eventID);
    }

    /**
     * @return array<DomainEvent>
     */
    public function update(
        UserUpdate $updateData,
        string $hashedNewPassword,
        string $eventID,
        EmailChangedEventFactoryInterface $emailChangedEventFactory,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory,
    ): array {
        $events = [];

        $events += $this->processNewEmail(
            $updateData->newEmail,
            $eventID,
            $emailChangedEventFactory
        );
        $events += $this->processNewPassword(
            $updateData->newPassword,
            $updateData->oldPassword,
            $eventID,
            $passwordChangedEventFactory
        );

        $this->initials = $updateData->newInitials;
        $this->password = $hashedNewPassword;

        return $events;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    /**
     * @return array<DomainEvent>
     */
    private function processNewEmail(
        string $newEmail,
        string $eventID,
        EmailChangedEventFactoryInterface $emailChangedEventFactory,
    ): array {
        $events = [];
        if ($newEmail !== $this->email) {
            $this->confirmed = false;
            $events[] =
                $emailChangedEventFactory->create($this, $eventID);
        }

        $this->email = $newEmail;
        return $events;
    }

    /**
     * @return array<DomainEvent>
     */
    private function processNewPassword(
        string $newPassword,
        string $oldPassword,
        string $eventID,
        PasswordChangedEventFactoryInterface $passwordChangedEventFactory
    ): array {
        $events = [];
        if ($newPassword !== $oldPassword) {
            $events[] =
                $passwordChangedEventFactory->create($this->email, $eventID);
        }
        return $events;
    }
}
