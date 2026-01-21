<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Factory\SqsDispatchFailureMetricFactoryInterface;
use App\Shared\Application\Observability\Metric\SqsDispatchFailureMetric;

/**
 * Factory for creating SQS dispatch failure metrics.
 *
 * Creates metrics with pure Value Objects - no service dependencies passed.
 */
final readonly class SqsDispatchFailureMetricFactory implements
    SqsDispatchFailureMetricFactoryInterface
{
    #[\Override]
    public function create(string $eventType): SqsDispatchFailureMetric
    {
        return new SqsDispatchFailureMetric(eventType: $eventType);
    }
}
