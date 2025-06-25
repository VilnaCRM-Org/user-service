<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class UserSignInDto
{
    public function __construct(
        public ?string $email = null,
        public ?string $password = null,
        public ?bool $fa = null,
    ) {
    }
}
