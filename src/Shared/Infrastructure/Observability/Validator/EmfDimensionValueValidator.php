<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Application\Validator\EmfKey;
use App\Shared\Application\Validator\EmfValue;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionKeyException;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionValueException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates EMF dimension values using Symfony Validator with compound constraints.
 *
 * Following SOLID:
 * - Single Responsibility: Only validates EmfDimensionValue and translates violations
 * - Dependency Inversion: Depends on ValidatorInterface abstraction
 * - Self-contained: Uses compound constraints directly, no external YAML config needed
 */
final readonly class EmfDimensionValueValidator implements EmfDimensionValueValidatorInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    #[\Override]
    public function validate(EmfDimensionValue $dimensionValue): void
    {
        $this->validateKey($dimensionValue->key());
        $this->validateValue($dimensionValue->value());
    }

    private function validateKey(string $key): void
    {
        $violations = $this->validator->validate($key, new EmfKey());

        if ($violations->count() > 0) {
            throw new InvalidEmfDimensionKeyException($violations->get(0)->getMessage());
        }
    }

    private function validateValue(string $value): void
    {
        $violations = $this->validator->validate($value, new EmfValue());

        if ($violations->count() > 0) {
            throw new InvalidEmfDimensionValueException($violations->get(0)->getMessage());
        }
    }
}
