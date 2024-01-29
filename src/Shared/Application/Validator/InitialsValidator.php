<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class InitialsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->validateFormat($value);
        $this->validateParts($value);
    }

    private function validateFormat(mixed $value): void
    {
        if (!preg_match('/^[^\d\s]+\s[^\d\s]+$/', $value)) {
            $this->addViolation('Invalid full name format');
        }
    }

    private function validateParts(mixed $value): void
    {
        if ($this->hasEmptyParts($value)) {
            $this->addViolation(
                'Name and surname should have at least 1 character'
            );
        }
    }

    private function hasEmptyParts(mixed $value): bool
    {
        $nameParts = explode(' ', $value);
        foreach ($nameParts as $part) {
            if (strlen($part) === 0) {
                return true;
            }
        }

        return false;
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
