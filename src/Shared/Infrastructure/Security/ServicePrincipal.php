<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class ServicePrincipal implements UserInterface
{
    /**
     * @param array<string> $roles
     */
    public function __construct(
        private string $identifier,
        private array $roles,
    ) {
    }

    /**
     * @return array<string>
     */
    #[\Override]
    public function getRoles(): array
    {
        return $this->roles;
    }

    #[\Override]
    public function eraseCredentials(): void
    {
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }
}
