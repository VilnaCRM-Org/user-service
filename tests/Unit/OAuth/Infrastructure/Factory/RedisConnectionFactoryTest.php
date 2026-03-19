<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Factory;

use App\OAuth\Infrastructure\Factory\RedisConnectionFactory;
use App\Tests\Unit\UnitTestCase;
use Redis;

final class RedisConnectionFactoryTest extends UnitTestCase
{
    public function testCreateReturnsRedisInstance(): void
    {
        $redis = RedisConnectionFactory::create('redis://redis:6379/0');

        $this->assertInstanceOf(Redis::class, $redis);
    }

    public function testCreateWithDefaultValues(): void
    {
        $redis = RedisConnectionFactory::create('redis://redis:6379');

        $this->assertInstanceOf(Redis::class, $redis);
    }
}
