<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Domain\Bus\Command\CommandResponseInterface;

final readonly class RefreshTokenCommandResponse implements CommandResponseInterface
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
