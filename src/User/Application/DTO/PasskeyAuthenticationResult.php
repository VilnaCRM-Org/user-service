<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class PasskeyAuthenticationResult
{
    public function __construct(
        private string $accessToken,
        private string $refreshToken,
        private bool $rememberMe
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }
}
