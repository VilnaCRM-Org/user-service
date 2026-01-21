<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\EventSubscriberFailureMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Metric emitted when an event subscriber fails during execution.
 *
 * Used for self-healing: triggers CloudWatch alarm -> GitHub issue -> AI fix.
 * Uses pure Value Objects without service dependencies (DDD compliant).
 */
final readonly class EventSubscriberFailureMetric extends BusinessMetric
{
    public function __construct(
        private string $subscriberClass,
        private string $eventType,
        float|int $value = 1
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public function name(): string
    {
        return 'EventSubscriberFailures';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new EventSubscriberFailureMetricDimensions(
            subscriberClass: $this->subscriberClass,
            eventType: $this->eventType
        );
    }
}
