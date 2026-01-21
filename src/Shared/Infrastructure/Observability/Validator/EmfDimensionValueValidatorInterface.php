<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;

/**
 * Validates EMF dimension values.
 *
 * Following SOLID:
 * - Interface Segregation: Focused on validating EmfDimensionValue only
 * - Dependency Inversion: Allows collection to depend on abstraction
 */
interface EmfDimensionValueValidatorInterface
{
    /**
     * Validates an EMF dimension value.
     *
     * @throws \App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionKeyException
     * @throws \App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionValueException
     */
    public function validate(EmfDimensionValue $dimensionValue): void;
}
