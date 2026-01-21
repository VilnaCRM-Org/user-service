<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\EndpointOperationMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Test metric for OrdersPlaced (pure Value Object - no factory)
 */
final readonly class TestOrdersPlacedMetric extends BusinessMetric
{
    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public function name(): string
    {
        return 'OrdersPlaced';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new EndpointOperationMetricDimensions(
            endpoint: 'Order',
            operation: 'create'
        );
    }
}
