<?php

declare(strict_types=1);

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\Command;

final readonly class SignUpCommand implements Command
{
    public function __construct(
        private string $email,
        private string $initials,
        private string $password
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}