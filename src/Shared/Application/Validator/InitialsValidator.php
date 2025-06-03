<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    private const MAX_INITIALS_LENGTH = 255;

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->shouldSkipValidation($value, $constraint)) {
            return;
        }

        $trimmed = trim($value);
        if ($this->isEmpty($trimmed) && !$this->isEmpty($value)) {
            $this->addViolation(
                $this->translator->trans('initials.spaces')
            );
        }
    }

    private function shouldSkipValidation(
        mixed $value,
        Constraint $constraint
    ): bool {
        return $this->isNull($value) ||
            ($constraint->isOptional() && $this->isEmpty($value));
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === '';
    }

    private function isNull(mixed $value): bool
    {
        return $value === null;
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
