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

    public function equals(self $other): bool
    {
        return $this->email === $other->email
            && $this->name === $other->name
            && $this->providerId === $other->providerId
            && $this->emailVerified === $other->emailVerified;
    }
}
