<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;

final class SignUpCommand implements Command
{
    public function __construct(
        private string $email,
        private string $initials,
        private string $password,
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
