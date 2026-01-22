<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Base class for business metrics
 *
 * Each metric type should extend this class and provide
 * its own name, dimensions, and default unit.
 */
abstract readonly class BusinessMetric
{
    public function __construct(
        private float|int $value,
        private MetricUnit $unit
    ) {
    }

    abstract public function name(): string;

    abstract public function dimensions(): MetricDimensionsInterface;

    public function value(): float|int
    {
        return $this->value;
    }

    public function unit(): MetricUnit
    {
        return $this->unit;
    }
}
