<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\ValueObject\EmfNamespaceValue;

/**
 * Validates EMF namespace values.
 *
 * Following SOLID:
 * - Interface Segregation: Focused on validating EmfNamespaceValue only
 * - Dependency Inversion: Allows factory to depend on abstraction
 */
interface EmfNamespaceValidatorInterface
{
    /**
     * Validates an EMF namespace value.
     *
     * @throws \App\Shared\Infrastructure\Observability\Exception\InvalidEmfNamespaceException
     */
    public function validate(EmfNamespaceValue $namespace): void;
}
