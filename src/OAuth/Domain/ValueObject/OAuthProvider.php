<?php

declare(strict_types=1);

namespace App\OAuth\Domain\ValueObject;

final readonly class OAuthProvider
{
    public function __construct(
        public string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
