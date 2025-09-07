<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Strategy;

use Symfony\Component\Validator\Constraint;

final readonly class ValidationSkipChecker
{
    public function shouldSkip(mixed $value, Constraint $constraint): bool
    {
        return $this->isNullValue($value) ||
               $this->isOptionalEmptyValue($value, $constraint);
    }

    private function isNullValue(mixed $value): bool
    {
        return $value === null;
    }

    private function isOptionalEmptyValue(
        mixed $value,
        Constraint $constraint
    ): bool {
        return \method_exists($constraint, 'isOptional')
            && $constraint->isOptional()
            && $value === '';
    }
}
