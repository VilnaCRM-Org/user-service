<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class RedisDatabaseMirror
{
    public function __construct(
        #[Autowire('%env(REDIS_URL)%')]
        private string $redisUrl,
    ) {
    }

    public function mirrorDefaultLimiterStateToHttpDatabase(): void
    {
        $sourceRedis = $this->connection(1);
        $targetRedis = $this->connection(0);

        foreach ($this->stringKeys($sourceRedis) as $key) {
            $this->copyKey($sourceRedis, $targetRedis, $key);
        }
    }

    public function flushDefaultAndHttpDatabases(): void
    {
        $this->connection(1)->flushDB();
        $this->connection(0)->flushDB();
    }

    /**
     * @return list<string>
     */
    private function stringKeys(\Redis $redis): array
    {
        $stringKeys = [];

        foreach ($redis->keys('*') as $key) {
            if (!is_string($key) || !$this->isStringKey($redis->type($key))) {
                continue;
            }

            $stringKeys[] = $key;
        }

        return $stringKeys;
    }

    private function copyKey(\Redis $sourceRedis, \Redis $targetRedis, string $key): void
    {
        $value = $sourceRedis->get($key);
        if (!is_string($value)) {
            return;
        }

        $targetRedis->set($key, $value);
        $this->copyTimeToLive($sourceRedis, $targetRedis, $key);
    }

    private function copyTimeToLive(\Redis $sourceRedis, \Redis $targetRedis, string $key): void
    {
        $ttlMilliseconds = $sourceRedis->pttl($key);
        if (is_int($ttlMilliseconds) && $ttlMilliseconds > 0) {
            $targetRedis->pexpire($key, $ttlMilliseconds);
        }
    }

    private function connection(int $databaseIndex): \Redis
    {
        $parts = parse_url($this->redisUrl);
        if (!is_array($parts)) {
            throw new RuntimeException('REDIS_URL is invalid.');
        }

        $host = $parts['host'] ?? null;
        if (!is_string($host) || $host === '') {
            throw new RuntimeException('REDIS_URL host is missing.');
        }

        $redis = new \Redis();
        $connected = $redis->connect($host, (int) ($parts['port'] ?? 6379));
        if ($connected !== true) {
            throw new RuntimeException('Failed to connect to Redis.');
        }

        $password = $parts['pass'] ?? null;
        if (is_string($password) && $password !== '') {
            $redis->auth($password);
        }

        $redis->select($databaseIndex);

        return $redis;
    }

    private function isStringKey(int|string $keyType): bool
    {
        return $keyType === 'string' || $keyType === 1;
    }
}
