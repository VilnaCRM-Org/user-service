<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

/**
 * Represents a single metric dimension key-value pair.
 */
final readonly class MetricDimension
{
    public function __construct(
        private string $key,
        private string $value
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): string
    {
        return $this->value;
    }
}
