<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserConfirmedEvent;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(['output', 'deleteMutationOutput'])]
    private Uuid $id;

    #[Groups(['output'])]
    private string $email;

    #[Groups(['output'])]
    private string $initials;

    private string $password;

    #[Groups(['output'])]
    private bool $confirmed;

    /**
     * @var array<string>
     */
    #[Groups(['output'])]
    private array $roles;

    public function __construct(
        string $email,
        string $initials,
        string $password,
        Uuid $id,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->initials = $initials;
        $this->password = $password;
        $this->roles = ['ROLE_USER'];
        $this->confirmed = false;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(Uuid $id): void
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

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
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
