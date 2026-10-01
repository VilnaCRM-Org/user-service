<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;

final readonly class AuthFailureMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $backend
    ) {
    }

    #[\Override]
    public function values(): MetricDimensions
    {
        return new MetricDimensions(new MetricDimension('backend', $this->backend));
    }
}
