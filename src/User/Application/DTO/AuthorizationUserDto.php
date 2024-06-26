<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Domain\ValueObject\UuidInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AuthorizationUserDto implements UserInterface
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
     * @return array<string>
     */
    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
