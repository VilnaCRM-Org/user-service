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

        $this->validateSpaces($value);
    }

    private function shouldSkipValidation(
        mixed $value,
        Constraint $constraint
    ): bool {
        return $value === null ||
            ($constraint->isOptional() && $value === '');
    }

    private function validateSpaces(mixed $value): void
    {
        $trimmedValue = trim($value);
        if ($trimmedValue === '' && strlen($value) > 0) {
            $this->addViolation(
                $this->translator->trans('initials.spaces')
            );
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
