<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Collection;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Tests\Unit\UnitTestCase;

final class EmfDimensionKeysTest extends UnitTestCase
{
    public function testCreatesWithKeys(): void
    {
        $keys = new EmfDimensionKeys('Endpoint', 'Operation');

        self::assertCount(2, $keys);
    }

    public function testReturnsAllKeys(): void
    {
        $keys = new EmfDimensionKeys('Endpoint', 'Operation');

        self::assertSame(['Endpoint', 'Operation'], $keys->all());
    }

    public function testIsIterable(): void
    {
        $keys = new EmfDimensionKeys('Endpoint', 'Operation');

        $items = [];
        foreach ($keys as $key) {
            $items[] = $key;
        }

        self::assertCount(2, $items);
        self::assertSame('Endpoint', $items[0]);
        self::assertSame('Operation', $items[1]);
    }

    public function testSerializesToNestedArrayFormat(): void
    {
        $keys = new EmfDimensionKeys('Endpoint', 'Operation');

        $json = $keys->jsonSerialize();

        self::assertSame([['Endpoint', 'Operation']], $json);
    }

    public function testCountReturnsNumberOfKeys(): void
    {
        $keys = new EmfDimensionKeys('A', 'B', 'C');

        self::assertCount(3, $keys);
    }
}
