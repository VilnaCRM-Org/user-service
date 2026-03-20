<?php

declare(strict_types=1);

namespace App\OAuth\Domain\ValueObject;

use DateTimeImmutable;

final readonly class OAuthStatePayload
{
    public function __construct(
        public string $provider,
        public string $codeVerifier,
        public string $flowBindingHash,
        public string $redirectUri,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
