<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->isNull($value) ||
            ($constraint->isOptional() && $this->isEmpty($value))
        ) {
            return;
        }

        $this->validateSpecialCharacters($value);
        $this->validateFormat($value);
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === '';
    }

    private function isNull(mixed $value): bool
    {
        return $value === null;
    }

    private function validateFormat(mixed $value): void
    {
        if (!preg_match('/^[^\d\s]+(\s[^\d\s]+)+$/', $value)) {
            $this->addViolation(
                $this->translator->trans('initials.invalid.format')
            );
        }
    }

    private function validateSpecialCharacters(mixed $value): void
    {
        if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $this->addViolation(
                $this->translator->trans('initials.invalid.characters')
            );
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
