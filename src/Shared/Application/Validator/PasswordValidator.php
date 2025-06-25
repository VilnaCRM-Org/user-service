<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordValidator extends ConstraintValidator
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 64;

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->shouldSkipValidation($value, $constraint)) {
            return;
        }

        $this->performAllValidations($value);
    }

    private function shouldSkipValidation(
        mixed $value,
        Constraint $constraint
    ): bool {
        return $value === null ||
            ($constraint->isOptional() && $value === '');
    }

    private function performAllValidations(mixed $value): void
    {
        $this->validateLength($value);
        $this->validateUppercase($value);
        $this->validateNumber($value);
    }

    private function validateLength(mixed $value): void
    {
        if ($this->isInvalidLength($value)) {
            $this->addViolation('password.invalid.length');
        }
    }

    private function validateUppercase(mixed $value): void
    {
        if ($this->hasNoUppercase($value)) {
            $this->addViolation('password.missing.uppercase');
        }
    }

    private function validateNumber(mixed $value): void
    {
        if ($this->hasNoNumber($value)) {
            $this->addViolation('password.missing.number');
        }
    }

    private function isInvalidLength(mixed $value): bool
    {
        $length = strlen($value);
        return $length < self::MIN_LENGTH || $length > self::MAX_LENGTH;
    }

    private function hasNoUppercase(mixed $value): bool
    {
        return !preg_match('/[A-Z]/', $value);
    }

    private function hasNoNumber(mixed $value): bool
    {
        return !preg_match('/[0-9]/', $value);
    }

    private function addViolation(string $messageKey): void
    {
        $message = $this->translator->trans($messageKey);
        $this->context->buildViolation($message)->addViolation();
    }
}
