<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final class SignInDto
{
    public bool $rememberMe = false;

    public function __construct(
        public string $email = '',
        #[\SensitiveParameter]
        public string $password = '',
    ) {
    }
}
