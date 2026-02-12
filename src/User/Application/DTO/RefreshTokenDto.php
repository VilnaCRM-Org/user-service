<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class RefreshTokenDto
{
    public function __construct(
        public string $refreshToken = '',
    ) {
    }
}
