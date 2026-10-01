<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Infrastructure\Factory\PhpRedisClientFactory;
use App\Tests\Unit\UnitTestCase;

final class PhpRedisClientFactoryTest extends UnitTestCase
{
    public function testCreatesNewDisconnectedClientEachTime(): void
    {
        $factory = new PhpRedisClientFactory();

        $first = $factory->create();
        $second = $factory->create();

        self::assertNotSame($first, $second);
        self::assertFalse($first->isConnected());
    }
}
