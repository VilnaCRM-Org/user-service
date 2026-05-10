<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

/**
 * Represents an AWS CloudWatch EMF namespace
 *
 * AWS CloudWatch namespace constraints:
 * - 1-256 characters
 * - Only ASCII alphanumeric and these characters: . - _ / # :
 * - Must contain at least one non-whitespace character
 *
 * Validation is performed by EmfNamespaceValidator using compound constraints.
 */
final readonly class EmfNamespaceValue
{
    public function __construct(
        private string $value
    ) {
    }

    public function value(): string
    {
        return $this->value;
    }
}
