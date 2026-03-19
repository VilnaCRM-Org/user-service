<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Factory;

use Redis;

/**
 * @psalm-suppress UnusedClass - Used via service container factory
 */
final class RedisConnectionFactory
{
    /** @infection-ignore-all - Creates real Redis connections, tested via integration */
    public static function create(string $redisUrl): Redis
    {
        $parsed = parse_url($redisUrl);

        $redis = new Redis();
        $redis->connect(
            (string) ($parsed['host'] ?? 'localhost'),
            (int) ($parsed['port'] ?? 6379),
        );

        $database = ltrim((string) ($parsed['path'] ?? '/0'), '/');
        if ($database !== '') {
            $redis->select((int) $database);
        }

        return $redis;
    }
}
