<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\EndpointOperationMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Test metric for Customer operations with distinct dimensions (pure Value Object - no factory)
 */
final readonly class TestCustomerMetric extends BusinessMetric
{
    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public function name(): string
    {
        return 'CustomersCreated';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new EndpointOperationMetricDimensions(
            endpoint: 'Customer',
            operation: 'create'
        );
    }
}
