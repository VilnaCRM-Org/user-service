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

    public function testCreateAuthenticatesWhenPasswordProvided(): void
    {
        $this->skipIfRedisUnavailable();

        $tempPassword = 'test_temp_pass_' . bin2hex(random_bytes(8));

        $admin = new Redis();
        $admin->connect('redis', 6379, 1);
        $admin->config('SET', 'requirepass', $tempPassword);

        try {
            $redis = RedisConnectionFactory::create(
                sprintf('redis://:%s@redis:6379/0', $tempPassword)
            );

            $this->assertInstanceOf(Redis::class, $redis);
        } finally {
            $admin->auth($tempPassword);
            $admin->config('SET', 'requirepass', '');
        }
    }

    private function skipIfRedisUnavailable(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped('PHP Redis extension is not installed');
        }

        set_error_handler(static fn (): bool => true);
        try {
            $redis = new Redis();
            $redis->connect('redis', 6379, 1);
        } catch (\RedisException) {
            $this->markTestSkipped('Redis server is not available');
        } finally {
            restore_error_handler();
        }
    }
}
