<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class BatchUserRegistrationInput
{
    public function __construct(
        public string $email,
        public string $initials,
        public string $password
    ) {
    }

    public function withEmail(string $email): self
    {
        return new self($email, $this->initials, $this->password);
    }
}
