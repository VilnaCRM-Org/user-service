<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

/**
 * Represents a single metric value in EMF format
 *
 * Contains the metric name and its value for the top-level EMF payload
 */
final readonly class EmfMetricValue
{
    public function __construct(
        private string $name,
        private float|int $value
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): float|int
    {
        return $this->value;
    }
}
