<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Factory\EventSubscriberFailureMetricFactoryInterface;
use App\Shared\Application\Observability\Metric\EventSubscriberFailureMetric;

/**
 * Factory for creating event subscriber failure metrics.
 *
 * Creates metrics with pure Value Objects - no service dependencies passed.
 */
final readonly class EventSubscriberFailureMetricFactory implements
    EventSubscriberFailureMetricFactoryInterface
{
    #[\Override]
    public function create(
        string $subscriberClass,
        string $eventType
    ): EventSubscriberFailureMetric {
        return new EventSubscriberFailureMetric(
            subscriberClass: $subscriberClass,
            eventType: $eventType
        );
    }
}
