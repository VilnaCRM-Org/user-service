<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final readonly class SetupTwoFactorCommandResponse
{
    public function __construct(
        private string $otpauthUri,
        private string $secret
    ) {
    }

    public function getOtpauthUri(): string
    {
        return $this->otpauthUri;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
