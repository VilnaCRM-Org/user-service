<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Domain\ValueObject\UuidInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AuthorizationUserDto implements
    UserInterface,
    PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $email,
        private string $initials,
        private string $password,
        private UuidInterface $id,
        private bool $confirmed
    ) {
    }

    /**
     * @return array
     *
     * @psalm-return array<never, never>
     */
    #[\Override]
    public function getRoles(): array
    {
        return [];
    }

    /**
     * @return string
     */
    #[\Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    #[\Override]
    public function eraseCredentials(): void
    {
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
