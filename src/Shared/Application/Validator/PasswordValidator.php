<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordValidator extends ConstraintValidator
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

        $this->validateLength($value);
        $this->validateUppercase($value);
        $this->validateNumber($value);
    }

    private function validateLength(mixed $value): void
    {
        if (!(strlen($value) >= 8 && strlen($value) <= 64)) {
            $this->addViolation(
                $this->translator->trans('password.invalid.length')
            );
        }
    }

    private function validateNumber(mixed $value): void
    {
        if (!preg_match('/[0-9]/', $value)) {
            $this->addViolation(
                $this->translator->trans('password.missing.number')
            );
        }
    }

    private function validateUppercase(mixed $value): void
    {
        if (!preg_match('/[A-Z]/', $value)) {
            $this->addViolation(
                $this->translator->trans('password.missing.uppercase')
            );
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)
            ->addViolation();
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === '';
    }

    private function isNull(mixed $value): bool
    {
        return $value === null;
    }
}
