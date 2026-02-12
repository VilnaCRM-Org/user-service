<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final readonly class CompleteTwoFactorCommandResponse
{
    public function __construct(
        private string $accessToken,
        private string $refreshToken,
        private ?int $recoveryCodesRemaining = null,
        private ?string $warningMessage = null,
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

    public function getRecoveryCodesRemaining(): ?int
    {
        return $this->recoveryCodesRemaining;
    }

    public function getWarningMessage(): ?string
    {
        return $this->warningMessage;
    }
}
