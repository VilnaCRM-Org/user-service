<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class SignInInput extends RequestInput
{
    private bool $rememberMe = false;

    public function __construct(
        public string $email,
        public string $password,
    ) {
    }

    public static function withRememberMe(string $email, string $password): self
    {
        $input = new self($email, $password);
        $input->rememberMe = true;

        return $input;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }
}
