<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;

/**
 * Pure Value Object for event subscriber failure dimensions.
 *
 * Uses Endpoint=EventBus, Operation=subscribe, plus Subscriber and EventType dimensions.
 * Contains only data - no service dependencies (DDD compliant).
 */
final readonly class EventSubscriberFailureMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $subscriberClass,
        private string $eventType
    ) {
    }

    #[\Override]
    public function values(): MetricDimensions
    {
        return new MetricDimensions(
            new MetricDimension('Endpoint', 'EventBus'),
            new MetricDimension('Operation', 'subscribe'),
            new MetricDimension('Subscriber', $this->extractClassName($this->subscriberClass)),
            new MetricDimension('EventType', $this->extractClassName($this->eventType))
        );
    }

    private function extractClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
