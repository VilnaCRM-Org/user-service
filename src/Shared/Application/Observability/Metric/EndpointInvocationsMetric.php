<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Metric for tracking API endpoint invocations.
 *
 * Uses pure Value Objects without service dependencies (DDD compliant).
 */
final readonly class EndpointInvocationsMetric extends EndpointOperationBusinessMetric
{
    public function __construct(
        private string $endpoint,
        private string $operation,
        float|int $value = 1
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    #[\Override]
    public function name(): string
    {
        return 'EndpointInvocations';
    }

    #[\Override]
    protected function endpoint(): string
    {
        return $this->endpoint;
    }

    #[\Override]
    protected function operation(): string
    {
        return $this->operation;
    }
}
