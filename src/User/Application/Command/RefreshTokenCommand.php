<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Application\DTO\RefreshTokenCommandResponse;

final class RefreshTokenCommand implements CommandInterface
{
    private RefreshTokenCommandResponse $response;

    public function __construct(
        public readonly string $refreshToken,
    ) {
    }

    public function getResponse(): RefreshTokenCommandResponse
    {
        return $this->response;
    }

    public function setResponse(
        RefreshTokenCommandResponse $response
    ): void {
        $this->response = $response;
    }

    public function setTokens(string $accessToken, string $refreshToken): void
    {
        $this->setResponse(new RefreshTokenCommandResponse($accessToken, $refreshToken));
    }
}
