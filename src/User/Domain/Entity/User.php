<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserConfirmedEvent;

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
        string $eventID
    ): UserConfirmedEvent {
        $this->confirmed = true;

        return new UserConfirmedEvent($token, $eventID);
    }

    /**
     * @return array<DomainEvent>
     */
    public function update(
        string $newEmail,
        string $newInitials,
        string $newPassword,
        string $oldPassword,
        string $hashedNewPassword,
        string $eventID,
    ): array {
        $events = [];

        $events += $this->processNewEmail($newEmail, $eventID);
        $events += $this->processNewPassword(
            $newPassword,
            $oldPassword,
            $eventID
        );

        $this->initials = $newInitials;
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
    private function processNewEmail(string $newEmail, string $eventID): array
    {
        $events = [];
        if ($newEmail !== $this->email) {
            $this->confirmed = false;
            $events[] = new EmailChangedEvent($this, $eventID);
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
        string $eventID
    ): array {
        $events = [];
        if ($newPassword !== $oldPassword) {
            $events[] = new PasswordChangedEvent($this->email, $eventID);
        }
        return $events;
    }
}
