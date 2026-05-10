<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

/**
 * @psalm-api
 */
final class PasskeySignUpOptionsDto
{
    /**
     * @psalm-api
     */
    public function __construct(
        private string $email = '',
        private string $initials = '',
        private string $displayName = ''
    ) {
    }

    public function emailValue(): string
    {
        return $this->email;
    }

    public function initialsValue(): string
    {
        return $this->initials;
    }

    public function displayNameValue(): string
    {
        return $this->displayName;
    }
}
