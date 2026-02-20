<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final class CompleteTwoFactorCommandResponse
{
    private bool $rememberMe = false;

    public function __construct(
        private string $accessToken,
        private string $refreshToken,
        private ?int $recoveryCodesRemaining = null,
        private ?string $warningMessage = null,
    ) {
    }

    public function withRememberMe(): static
    {
        $clone = clone $this;
        $clone->rememberMe = true;

        return $clone;
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

    public function getRecoveryCodesRemaining(): ?int
    {
        return $this->recoveryCodesRemaining;
    }

    public function getWarningMessage(): ?string
    {
        return $this->warningMessage;
    }
}
