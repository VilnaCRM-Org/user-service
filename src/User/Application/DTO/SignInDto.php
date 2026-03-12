<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use LogicException;

final class SignInDto
{
    private bool $rememberMe = false;

    public function __construct(
        public string|int|null $email = '',
        #[\SensitiveParameter]
        public string|int|null $password = '',
    ) {
    }

    public function emailValue(): string
    {
        return $this->validatedStringValue($this->email, 'email');
    }

    public function passwordValue(): string
    {
        return $this->validatedStringValue($this->password, 'password');
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }

    private function validatedStringValue(
        string|int|null $value,
        string $field
    ): string {
        if (is_string($value)) {
            return $value;
        }

        throw new LogicException(
            sprintf(
                'Expected "%s" to be a string after request validation.',
                $field
            )
        );
    }
}
