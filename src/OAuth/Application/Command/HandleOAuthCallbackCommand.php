<?php

declare(strict_types=1);

namespace App\OAuth\Application\Command;

use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\Shared\Domain\Bus\Command\CommandInterface;

/**
 * @psalm-api
 */
final class HandleOAuthCallbackCommand implements CommandInterface
{
    private HandleOAuthCallbackResponse $response;

    public function __construct(
        public readonly string $provider,
        #[\SensitiveParameter]
        public readonly string $code,
        #[\SensitiveParameter]
        public readonly string $state,
        #[\SensitiveParameter]
        public readonly string $flowBindingToken,
        public readonly string $ipAddress,
        public readonly string $userAgent,
    ) {
    }

    public function getResponse(): HandleOAuthCallbackResponse
    {
        return $this->response;
    }

    public function setResponse(HandleOAuthCallbackResponse $response): void
    {
        $this->response = $response;
    }
}
