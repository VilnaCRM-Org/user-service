<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Application\Validator\EmfNamespace;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfNamespaceException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfNamespaceValue;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates EMF namespace values using Symfony Validator with compound constraints.
 *
 * Following SOLID:
 * - Single Responsibility: Only validates EmfNamespaceValue and translates violations
 * - Dependency Inversion: Depends on ValidatorInterface abstraction
 * - Self-contained: Uses compound constraint directly, no external YAML config needed
 */
final readonly class EmfNamespaceValidator implements EmfNamespaceValidatorInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function validate(EmfNamespaceValue $namespace): void
    {
        $violations = $this->validator->validate($namespace->value(), new EmfNamespace());

        if ($violations->count() > 0) {
            throw new InvalidEmfNamespaceException($violations->get(0)->getMessage());
        }
    }
}
