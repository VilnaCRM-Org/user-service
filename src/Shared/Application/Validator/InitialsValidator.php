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
        $helper = new BaseValidationHelper(
            $this->translator,
            $this->context
        );

        if ($helper->shouldSkipValidation($value, $constraint)) {
            return;
        }

        $this->validateInitials((string) $value, $helper);
    }

    private function validateInitials(
        string $value,
        BaseValidationHelper $helper
    ): void {
        if ($value === '') {
            return;
        }

        if ($helper->isOnlyWhitespace($value)) {
            $helper->addViolation('initials.spaces');
        }
    }
}
