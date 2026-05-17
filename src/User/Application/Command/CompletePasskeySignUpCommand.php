<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Application\DTO\PasskeyAuthenticationResult;

final class CompletePasskeySignUpCommand implements CommandInterface
{
    private PasskeyAuthenticationResult $response;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function __construct(
        public readonly string $challengeId,
        public readonly array $credential,
        public readonly string $label,
        public readonly bool $rememberMe,
        public readonly string $ipAddress,
        public readonly string $userAgent
    ) {
    }

    public function getResponse(): PasskeyAuthenticationResult
    {
        return $this->response;
    }

    public function setResponse(PasskeyAuthenticationResult $response): void
    {
        $this->response = $response;
    }
}
