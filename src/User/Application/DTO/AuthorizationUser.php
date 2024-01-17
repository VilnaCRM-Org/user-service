<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Domain\ValueObject\UuidInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthorizationUser implements UserInterface
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

    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function eraseCredentials()
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
