<?php

declare(strict_types=1);

namespace App\OAuth\Application\Command;

use App\OAuth\Application\DTO\InitiateOAuthResponse;
use App\Shared\Domain\Bus\Command\CommandInterface;

/**
 * @psalm-api
 */
final class InitiateOAuthCommand implements CommandInterface
{
    private InitiateOAuthResponse $response;

    public function __construct(
        public readonly string $provider,
        public readonly string $redirectUri,
    ) {
    }

    public function getResponse(): InitiateOAuthResponse
    {
        return $this->response;
    }

    public function setResponse(InitiateOAuthResponse $response): void
    {
        $this->response = $response;
    }
}
