<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Domain\ValueObject\UuidInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthorizationUser implements UserInterface
{
    public function __construct(
        private string $email,
        private string $initials,
        private string $password,
        private UuidInterface $id,
        private bool $confirmed,
        private array $roles
    ) {
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
