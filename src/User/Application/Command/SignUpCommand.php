<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class SignUpCommand implements CommandInterface
{
    public SignUpCommandResponse $response;

    public function __construct(
        public readonly string $email,
        public readonly string $initials,
        public readonly string $password,
    ) {
    }

    public function getResponse(): SignUpCommandResponse
    {
        return $this->response;
    }

    public function setResponse(SignUpCommandResponse $response): void
    {
        $this->response = $response;
    }
}
