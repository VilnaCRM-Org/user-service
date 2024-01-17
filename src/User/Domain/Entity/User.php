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

    /**
     * @var array<string>
     */
    private array $roles;

    public function __construct(
        private string $email,
        private string $initials,
        private string $password,
        private UuidInterface $id,
    ) {
        $this->roles = ['ROLE_USER'];
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

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function confirm(ConfirmationToken $token): UserConfirmedEvent
    {
        $this->confirmed = true;

        return new UserConfirmedEvent($token);
    }

    /**
     * @return array<DomainEvent>
     */
    public function update(
        string $newEmail,
        string $newInitials,
        string $newPassword,
        string $oldPassword,
        string $hashedNewPassword
    ): array {
        $events = [];

        if ($newEmail !== $this->email) {
            $this->confirmed = false;
            $events[] = new EmailChangedEvent($this);
        }

        $this->email = $newEmail;

        if ($newPassword !== $oldPassword) {
            $events[] = new PasswordChangedEvent($this->email);
        }

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
}
