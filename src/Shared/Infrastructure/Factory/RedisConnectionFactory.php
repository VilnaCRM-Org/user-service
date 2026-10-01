<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

/**
 * Symfony service factory for the Redis connections (REDIS_URL and
 * REDIS_LOCKOUT_URL). IAM authentication is selected explicitly by a
 * non-empty REDIS_IAM_USER_ID; it never falls back to the standard path.
 */
final readonly class RedisConnectionFactory implements RedisConnectionFactoryInterface
{
    public function __construct(
        private string $iamUserId,
        private RedisConnectionFactoryInterface $standardConnectionFactory,
        private RedisConnectionFactoryInterface $iamConnectionFactory
    ) {
    }

    #[\Override]
    public function create(string $dsn): \Redis
    {
        if ($this->iamUserId === '') {
            return $this->standardConnectionFactory->create($dsn);
        }

        return $this->iamConnectionFactory->create($dsn);
    }
}
