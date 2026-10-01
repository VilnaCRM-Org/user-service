<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Infrastructure\Factory\StandardRedisConnectionFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

final class StandardRedisConnectionFactoryTest extends UnitTestCase
{
    public function testDelegatesDsnToSymfonyRedisAdapter(): void
    {
        $dsn = sprintf(
            'redis://%s:%d/%d?lazy=1',
            $this->faker->domainWord(),
            $this->faker->numberBetween(1024, 65535),
            $this->faker->numberBetween(0, 15)
        );

        $connection = $this->factory()->create($dsn);

        self::assertInstanceOf(\Redis::class, $connection);
    }

    public function testRejectsDsnThatIsNotASinglePhpRedisConnection(): void
    {
        $host = $this->faker->domainWord();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The Redis DSN must resolve to a single phpredis connection.'
        );

        $this->factory()->create(
            sprintf('redis:?host[%s:6379]&host[%s:6380]&lazy=1', $host, $host)
        );
    }

    private function factory(): StandardRedisConnectionFactory
    {
        return new StandardRedisConnectionFactory(
            \Closure::fromCallable([RedisAdapter::class, 'createConnection'])
        );
    }
}
