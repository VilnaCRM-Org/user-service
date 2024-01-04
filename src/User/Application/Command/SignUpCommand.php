<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;

final class SignUpCommand implements Command
{
    public function __construct(
        public readonly string $email,
        public readonly string $initials,
        public readonly string $password,
    ) {
    }

    public SignUpCommandResponse $response;

    public function getResponse(): SignUpCommandResponse
    {
        return $this->response;
    }

    public function setResponse(SignUpCommandResponse $response): void
    {
        $this->response = $response;
    }
}
