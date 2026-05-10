<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class SetupTwoFactorCommandResponse
{
    public function __construct(
        private string $otpauthUri,
        private string $secret
    ) {
    }

    /**
     * @psalm-api
     */
    public function getOtpauthUri(): string
    {
        return $this->otpauthUri;
    }

    /**
     * @psalm-api
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
}
