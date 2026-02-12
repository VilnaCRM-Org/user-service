<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final readonly class SignInCommandResponse
{
    public function __construct(
        private bool $twoFactorEnabled,
        private ?string $accessToken = null,
        private ?string $refreshToken = null,
        private ?string $pendingSessionId = null,
    ) {
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getPendingSessionId(): ?string
    {
        return $this->pendingSessionId;
    }
}
