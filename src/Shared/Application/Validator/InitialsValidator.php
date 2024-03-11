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

        $this->validateFormat($value);
        $this->validateParts($value);
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
        if (!preg_match('/^\D*$/', $value)) {
            $this->addViolation(
                $this->translator->trans('initials.invalidFormat')
            );
        }
    }

    private function validateParts(mixed $value): void
    {
        if ($this->hasEmptyParts($value)) {
            $this->addViolation(
                $this->translator->trans('initials.invalidParts')
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
