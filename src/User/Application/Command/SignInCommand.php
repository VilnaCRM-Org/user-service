<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final readonly class SignInCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false,
    ) {
    }
}
