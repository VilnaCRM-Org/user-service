<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class RequestPasswordResetDto
{
    public function __construct(
        public ?string $email = null
    ) {
    }
}