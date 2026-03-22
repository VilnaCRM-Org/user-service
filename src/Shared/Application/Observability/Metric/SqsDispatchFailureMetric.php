<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Shared\Application\Observability\Metric\ValueObject\SqsDispatchFailureMetricDimensions;

/**
 * Metric emitted when dispatching domain event to SQS fails.
 *
 * Used for self-healing: triggers CloudWatch alarm -> GitHub issue -> AI fix.
 * Uses pure Value Objects without service dependencies (DDD compliant).
 */
final readonly class SqsDispatchFailureMetric extends BusinessMetric
{
    public function __construct(
        private string $eventType,
        float|int $value = 1
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    #[\Override]
    public function name(): string
    {
        return 'SqsDispatchFailures';
    }

    #[\Override]
    public function dimensions(): MetricDimensionsInterface
    {
        return new SqsDispatchFailureMetricDimensions(
            eventType: $this->eventType
        );
    }
}
