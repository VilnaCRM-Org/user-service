<?php

declare(strict_types=1);

namespace App\User\Application\Service;

final readonly class IssuedSession
{
    public function __construct(
        public string $sessionId,
        public string $accessToken,
        public string $refreshToken,
    ) {
    }
}
