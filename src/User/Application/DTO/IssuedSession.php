<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class IssuedSession
{
    public function __construct(
        public string $sessionId,
        public string $accessToken,
        public string $refreshToken,
    ) {
    }
}
