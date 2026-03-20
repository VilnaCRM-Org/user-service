<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Factory;

use App\OAuth\Infrastructure\Factory\RedisConnectionFactory;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;
use Redis;

final class RedisConnectionFactoryTest extends UnitTestCase
{
    public function testCreateThrowsForInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Redis URL');

        RedisConnectionFactory::create('http:///');
    }

    public function testCreateReturnsRedisInstance(): void
    {
        $this->skipIfRedisUnavailable();

        $redis = RedisConnectionFactory::create(
            'redis://redis:6379/0'
        );

        $this->assertInstanceOf(Redis::class, $redis);
    }

    public function testCreateWithDefaultValues(): void
    {
        $this->skipIfRedisUnavailable();

        $redis = RedisConnectionFactory::create(
            'redis://redis:6379'
        );

        $this->assertInstanceOf(Redis::class, $redis);
    }

    private function skipIfRedisUnavailable(): void
    {
        try {
            $redis = new Redis();
            $redis->connect('redis', 6379, 1);
        } catch (\RedisException) {
            $this->markTestSkipped('Redis server is not available');
        }
    }
}
