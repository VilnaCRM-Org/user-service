<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Connection without IAM: the DSN (including any password or database path)
 * is handled by Symfony RedisAdapter::createConnection() as before. Used when
 * REDIS_IAM_USER_ID is empty, for local development and tests.
 */
final readonly class StandardRedisConnectionFactory implements RedisConnectionFactoryInterface
{
    public function __construct(
        private \Closure $symfonyConnectionFactory
    ) {
    }

    #[\Override]
    public function create(string $dsn): \Redis
    {
        $connection = ($this->symfonyConnectionFactory)($dsn);

        if (!$connection instanceof \Redis) {
            throw new InvalidArgumentException(
                'The Redis DSN must resolve to a single phpredis connection.'
            );
        }

        return $connection;
    }
}
