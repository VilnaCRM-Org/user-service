<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BaseValidationHelper
{
    public function __construct(
        private TranslatorInterface $translator,
        private ExecutionContextInterface $context
    ) {
    }

    public function shouldSkipValidation(
        mixed $value,
        Constraint $constraint
    ): bool {
        if ($value === null) {
            return true;
        }

        if ($value === '' && $constraint->isOptional()) {
            return true;
        }

        return false;
    }

    public function isNullOrEmpty(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    public function isOptionalAndEmpty(
        mixed $value,
        Constraint $constraint
    ): bool {
        return $value === '' && $constraint->isOptional();
    }

    public function addViolation(string $messageKey): void
    {
        $message = $this->translator->trans($messageKey);
        $this->context->buildViolation($message)->addViolation();
    }

    public function isOnlyWhitespace(string $value): bool
    {
        return trim($value) === '';
    }

    public function isLengthInvalid(
        string $value,
        int $minLength,
        int $maxLength
    ): bool {
        $length = strlen($value);
        return $length < $minLength || $length > $maxLength;
    }

    public function hasNoNumbers(string $value): bool
    {
        return !preg_match('/[0-9]/', $value);
    }

    public function hasNoUppercase(string $value): bool
    {
        return !preg_match('/[A-Z]/', $value);
    }
}
