<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class SignInDto
{
    public function __construct(
        public string $email = '',
        #[\SensitiveParameter]
        public string $password = '',
        public bool $rememberMe = false,
    ) {
    }
}
