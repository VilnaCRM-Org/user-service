<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;

/**
 * Pure Value Object for SQS dispatch failure dimensions.
 *
 * Uses Endpoint=EventBus, Operation=dispatch, plus EventType dimension.
 * Contains only data - no service dependencies (DDD compliant).
 */
final readonly class SqsDispatchFailureMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $eventType
    ) {
    }

    public function values(): MetricDimensions
    {
        return new MetricDimensions(
            new MetricDimension('Endpoint', 'EventBus'),
            new MetricDimension('Operation', 'dispatch'),
            new MetricDimension('EventType', $this->extractClassName($this->eventType))
        );
    }

    private function extractClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
