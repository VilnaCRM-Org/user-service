<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Tests\Unit\UnitTestCase;

final class MetricCollectionTest extends UnitTestCase
{
    public function testEmptyCollectionIsEmpty(): void
    {
        $collection = new MetricCollection();

        self::assertTrue($collection->isEmpty());
        self::assertCount(0, $collection);
    }

    public function testCollectionWithMetricsIsNotEmpty(): void
    {
        $metric = new EndpointInvocationsMetric('Customer', 'create');
        $collection = new MetricCollection($metric);

        self::assertFalse($collection->isEmpty());
        self::assertCount(1, $collection);
    }

    public function testCanIterateOverMetrics(): void
    {
        $metric1 = new EndpointInvocationsMetric('Customer', 'create');
        $metric2 = new EndpointInvocationsMetric('Customer', 'update');
        $collection = new MetricCollection($metric1, $metric2);

        $items = [];
        foreach ($collection as $metric) {
            $items[] = $metric;
        }

        self::assertCount(2, $items);
        self::assertSame($metric1, $items[0]);
        self::assertSame($metric2, $items[1]);
    }

    public function testAllReturnsAllMetrics(): void
    {
        $metric1 = new EndpointInvocationsMetric('Customer', 'create');
        $metric2 = new EndpointInvocationsMetric('Customer', 'update');
        $collection = new MetricCollection($metric1, $metric2);

        $all = $collection->all();

        self::assertSame([$metric1, $metric2], $all);
    }
}
