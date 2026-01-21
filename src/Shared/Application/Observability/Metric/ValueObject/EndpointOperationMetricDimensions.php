<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;

/**
 * Pure Value Object for endpoint operation dimensions.
 *
 * Contains only data - no service dependencies (DDD compliant).
 */
final readonly class EndpointOperationMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $endpoint,
        private string $operation
    ) {
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function values(): MetricDimensions
    {
        return new MetricDimensions(
            new MetricDimension('Endpoint', $this->endpoint),
            new MetricDimension('Operation', $this->operation)
        );
    }
}
