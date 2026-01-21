<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\EndpointOperationMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Test metric for OrderValue (pure Value Object - no factory)
 */
final readonly class TestOrderValueMetric extends BusinessMetric
{
    public function __construct(float|int $value)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::NONE));
    }

    public function name(): string
    {
        return 'OrderValue';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new EndpointOperationMetricDimensions(
            endpoint: 'Order',
            operation: 'create'
        );
    }
}
