<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final class SignInDto
{
    private bool $rememberMe = false;

    public function __construct(
        public string $email = '',
        #[\SensitiveParameter]
        public string $password = '',
    ) {
    }

    public function emailValue(): string
    {
        return $this->email;
    }

    public function passwordValue(): string
    {
        return $this->password;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }
}
