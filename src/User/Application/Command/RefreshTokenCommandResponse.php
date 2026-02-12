<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final readonly class RefreshTokenCommandResponse
{
    public function __construct(
        private string $accessToken,
        private string $refreshToken,
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
}
