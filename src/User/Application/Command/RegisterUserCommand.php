<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $initials,
        public readonly string $password,
    ) {
    }
}
