<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Collection;

use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Tests\Unit\UnitTestCase;

final class EmfMetricDefinitionCollectionTest extends UnitTestCase
{
    public function testCreatesEmptyCollection(): void
    {
        $collection = new EmfMetricDefinitionCollection();

        self::assertTrue($collection->isEmpty());
        self::assertCount(0, $collection);
    }

    public function testCreatesCollectionWithDefinitions(): void
    {
        $collection = new EmfMetricDefinitionCollection(
            new EmfMetricDefinition('Metric1', 'Count'),
            new EmfMetricDefinition('Metric2', 'None')
        );

        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection);
    }

    public function testAddReturnsNewCollection(): void
    {
        $collection = new EmfMetricDefinitionCollection(
            new EmfMetricDefinition('Metric1', 'Count')
        );

        $newCollection = $collection->add(new EmfMetricDefinition('Metric2', 'None'));

        self::assertNotSame($collection, $newCollection);
        self::assertCount(1, $collection);
        self::assertCount(2, $newCollection);
    }

    public function testSerializesToArrayOfDefinitions(): void
    {
        $collection = new EmfMetricDefinitionCollection(
            new EmfMetricDefinition('CustomersCreated', 'Count'),
            new EmfMetricDefinition('OrderValue', 'None')
        );

        $json = $collection->jsonSerialize();

        self::assertCount(2, $json);
        self::assertSame(['Name' => 'CustomersCreated', 'Unit' => 'Count'], $json[0]);
        self::assertSame(['Name' => 'OrderValue', 'Unit' => 'None'], $json[1]);
    }

    public function testIsIterable(): void
    {
        $collection = new EmfMetricDefinitionCollection(
            new EmfMetricDefinition('Metric1', 'Count')
        );

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }

        self::assertCount(1, $items);
        self::assertInstanceOf(EmfMetricDefinition::class, $items[0]);
    }

    public function testAllReturnsAllDefinitions(): void
    {
        $collection = new EmfMetricDefinitionCollection(
            new EmfMetricDefinition('Metric1', 'Count'),
            new EmfMetricDefinition('Metric2', 'None')
        );

        $all = $collection->all();

        self::assertCount(2, $all);
        self::assertInstanceOf(EmfMetricDefinition::class, $all[0]);
        self::assertInstanceOf(EmfMetricDefinition::class, $all[1]);
    }
}
