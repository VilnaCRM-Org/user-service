<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

/**
 * An ElastiCache IAM authentication token and the Unix time until which
 * ElastiCache accepts it: the shorter of the 15-minute token lifetime and the
 * expiry of the credentials that signed it.
 */
final readonly class RedisIamAuthToken
{
    public function __construct(
        private string $value,
        private int $validUntil
    ) {
    }

    public function value(): string
    {
        return $this->value;
    }

    public function validUntil(): int
    {
        return $this->validUntil;
    }
}
