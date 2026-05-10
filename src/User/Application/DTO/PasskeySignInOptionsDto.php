<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

/**
 * @psalm-api
 */
final class PasskeySignInOptionsDto
{
    private bool $rememberMe = false;

    /**
     * @psalm-api
     */
    public function __construct(private string $email = '')
    {
    }

    public function emailValue(): string
    {
        return $this->email;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    /**
     * @psalm-api
     */
    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }
}
