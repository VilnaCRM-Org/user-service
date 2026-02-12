<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class SignInCommand implements CommandInterface
{
    private SignInCommandResponse $response;

    public function __construct(
        public readonly string $email,
        #[\SensitiveParameter]
        public readonly string $password,
        public readonly bool $rememberMe,
        public readonly string $ipAddress,
        public readonly string $userAgent,
    ) {
    }

    public function getResponse(): SignInCommandResponse
    {
        return $this->response;
    }

    public function setResponse(SignInCommandResponse $response): void
    {
        $this->response = $response;
    }
}
