<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Collection;

use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;
use App\Tests\Unit\UnitTestCase;

final class EmfMetricValueCollectionTest extends UnitTestCase
{
    public function testCreatesEmptyCollection(): void
    {
        $collection = new EmfMetricValueCollection();

        self::assertTrue($collection->isEmpty());
        self::assertCount(0, $collection);
    }

    public function testCreatesCollectionWithValues(): void
    {
        $collection = new EmfMetricValueCollection(
            new EmfMetricValue('Metric1', 10),
            new EmfMetricValue('Metric2', 20.5)
        );

        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection);
    }

    public function testAddReturnsNewCollection(): void
    {
        $collection = new EmfMetricValueCollection(
            new EmfMetricValue('Metric1', 10)
        );

        $newCollection = $collection->add(new EmfMetricValue('Metric2', 20));

        self::assertNotSame($collection, $newCollection);
        self::assertCount(1, $collection);
        self::assertCount(2, $newCollection);
    }

    public function testReturnsAllValues(): void
    {
        $collection = new EmfMetricValueCollection(
            new EmfMetricValue('Metric1', 10)
        );

        $all = $collection->all();

        self::assertCount(1, $all);
        self::assertInstanceOf(EmfMetricValue::class, $all[0]);
    }

    public function testConvertsToAssociativeArray(): void
    {
        $collection = new EmfMetricValueCollection(
            new EmfMetricValue('CustomersCreated', 5),
            new EmfMetricValue('OrderValue', 99.99)
        );

        $array = $collection->toAssociativeArray();

        self::assertSame(['CustomersCreated' => 5, 'OrderValue' => 99.99], $array);
    }

    public function testIsIterable(): void
    {
        $collection = new EmfMetricValueCollection(
            new EmfMetricValue('Metric1', 10)
        );

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }

        self::assertCount(1, $items);
        self::assertInstanceOf(EmfMetricValue::class, $items[0]);
    }

    public function testThrowsExceptionOnDuplicateNames(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('Duplicate metric names detected');

        new EmfMetricValueCollection(
            new EmfMetricValue('Metric1', 10),
            new EmfMetricValue('Metric1', 20)
        );
    }
}
