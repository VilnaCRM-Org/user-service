<?php

declare(strict_types=1);

namespace App\OAuth\Domain\ValueObject;

use InvalidArgumentException;

final readonly class OAuthProvider
{
    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new InvalidArgumentException(
                'OAuth provider cannot be empty'
            );
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
