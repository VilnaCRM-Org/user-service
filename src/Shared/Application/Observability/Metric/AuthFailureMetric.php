<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\AuthFailureMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Log metric auth_failure{backend=...}, emitted when the app cannot
 * authenticate to a backing store (for example Redis IAM AUTH).
 */
final readonly class AuthFailureMetric extends BusinessMetric
{
    public function __construct(
        private string $backend,
        float|int $value = 1
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    /**
     * @psalm-return 'auth_failure'
     */
    #[\Override]
    public function name(): string
    {
        return 'auth_failure';
    }

    /**
     * @return AuthFailureMetricDimensions
     */
    #[\Override]
    public function dimensions(): MetricDimensionsInterface
    {
        return new AuthFailureMetricDimensions(backend: $this->backend);
    }
}
