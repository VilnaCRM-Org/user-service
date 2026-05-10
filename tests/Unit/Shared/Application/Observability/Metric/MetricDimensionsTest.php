<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\UnitTestCase;

final class MetricDimensionsTest extends UnitTestCase
{
    public function testDimensionExposesKeyAndValue(): void
    {
        $dimension = new MetricDimension('Endpoint', 'Customer');

        self::assertSame('Endpoint', $dimension->key());
        self::assertSame('Customer', $dimension->value());
    }

    public function testDimensionsCollectionSupportsCountAndIteration(): void
    {
        $dimensions = (new MetricDimensionsFactory())
            ->endpointOperation('Customer', 'create');

        self::assertCount(2, $dimensions);

        $keys = [];
        foreach ($dimensions as $dimension) {
            $keys[] = $dimension->key();
        }

        self::assertSame(['Endpoint', 'Operation'], $keys);
    }

    public function testGetReturnsValueOrNull(): void
    {
        $dimensions = new MetricDimensions(new MetricDimension('Endpoint', 'Customer'));

        self::assertSame('Customer', $dimensions->get('Endpoint'));
        self::assertNull($dimensions->get('Operation'));
    }

    public function testContainsChecksExpectedKeyValuePair(): void
    {
        $dimensions = new MetricDimensions(
            new MetricDimension('Endpoint', 'Customer'),
            new MetricDimension('Operation', 'create')
        );

        self::assertTrue($dimensions->contains(new MetricDimension('Operation', 'create')));
        self::assertFalse($dimensions->contains(new MetricDimension('Operation', 'delete')));
    }

    public function testToAssociativeArrayReturnsKeyValueMap(): void
    {
        $dimensions = (new MetricDimensionsFactory())->endpointOperationWith(
            'Customer',
            'create',
            new MetricDimension('PaymentMethod', 'card')
        );

        self::assertSame([
            'Endpoint' => 'Customer',
            'Operation' => 'create',
            'PaymentMethod' => 'card',
        ], $dimensions->toAssociativeArray());
    }

    public function testThrowsExceptionOnDuplicateKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate metric dimension keys detected');

        new MetricDimensions(
            new MetricDimension('Endpoint', 'Customer'),
            new MetricDimension('Endpoint', 'Order')
        );
    }
}
