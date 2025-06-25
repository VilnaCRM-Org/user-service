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
        if ($this->shouldSkipValidation($value, $constraint)) {
            return;
        }

        if ($this->isOnlySpaces($value)) {
            $this->addTranslatedViolation('initials.spaces');
        }
    }

    private function shouldSkipValidation(
        mixed $value,
        Constraint $constraint
    ): bool {
        if ($value === null) {
            return true;
        }

        if ($constraint->isOptional() && $value === '') {
            return true;
        }

        return false;
    }

    private function isOnlySpaces(mixed $value): bool
    {
        return $value !== '' && trim($value) === '';
    }

    private function addTranslatedViolation(string $messageKey): void
    {
        $message = $this->translator->trans($messageKey);
        $this->context->buildViolation($message)->addViolation();
    }
}
