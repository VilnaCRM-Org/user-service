<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Infrastructure\Factory\RedisConnectionFactory;
use App\Shared\Infrastructure\Factory\RedisConnectionFactoryInterface;
use App\Tests\Unit\UnitTestCase;

final class RedisConnectionFactoryTest extends UnitTestCase
{
    public function testUsesStandardConnectionWhenIamUserIdIsEmpty(): void
    {
        $dsn = $this->faker->url();
        $connection = $this->createMock(\Redis::class);
        $standard = $this->createMock(RedisConnectionFactoryInterface::class);
        $standard->expects(self::once())->method('create')->with($dsn)->willReturn($connection);
        $iam = $this->createMock(RedisConnectionFactoryInterface::class);
        $iam->expects(self::never())->method('create');

        $factory = new RedisConnectionFactory('', $standard, $iam);

        self::assertSame($connection, $factory->create($dsn));
    }

    public function testUsesIamConnectionWhenIamUserIdIsSet(): void
    {
        $dsn = $this->faker->url();
        $connection = $this->createMock(\Redis::class);
        $standard = $this->createMock(RedisConnectionFactoryInterface::class);
        $standard->expects(self::never())->method('create');
        $iam = $this->createMock(RedisConnectionFactoryInterface::class);
        $iam->expects(self::once())->method('create')->with($dsn)->willReturn($connection);

        $factory = new RedisConnectionFactory($this->faker->userName(), $standard, $iam);

        self::assertSame($connection, $factory->create($dsn));
    }

    public function testIamFailureNeverFallsBackToStandardConnection(): void
    {
        $standard = $this->createMock(RedisConnectionFactoryInterface::class);
        $standard->expects(self::never())->method('create');
        $iam = $this->createMock(RedisConnectionFactoryInterface::class);
        $iam->method('create')->willThrowException(new \RedisException('WRONGPASS'));

        $this->expectException(\RedisException::class);

        (new RedisConnectionFactory($this->faker->userName(), $standard, $iam))
            ->create($this->faker->url());
    }
}
