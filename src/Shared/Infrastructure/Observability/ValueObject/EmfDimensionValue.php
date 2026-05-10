<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

/**
 * Represents a single dimension key-value pair in EMF format
 *
 * AWS CloudWatch EMF constraints:
 * - Keys: 1-255 chars, ASCII only, at least one non-whitespace, cannot start with ':'
 * - Values: 1-1024 chars, ASCII only, at least one non-whitespace
 * - No ASCII control characters allowed in either
 *
 * Validation is performed by EmfDimensionValueValidator using compound constraints.
 */
final readonly class EmfDimensionValue
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
