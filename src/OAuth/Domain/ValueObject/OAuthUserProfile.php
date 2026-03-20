<?php

declare(strict_types=1);

namespace App\OAuth\Domain\ValueObject;

final readonly class OAuthUserProfile
{
    public function __construct(
        public string $email,
        public string $name,
        public string $providerId,
        public bool $emailVerified,
    ) {
    }
}
