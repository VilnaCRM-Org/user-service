<?php

declare(strict_types=1);

namespace App\OAuth\Application\DTO;

/**
 * @psalm-api
 */
final readonly class HandleOAuthCallbackResponse
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
