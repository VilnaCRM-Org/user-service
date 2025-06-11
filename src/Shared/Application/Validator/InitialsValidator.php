<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    private BaseValidationHelper $helper;

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->helper = new BaseValidationHelper(
            $this->translator,
            $this->context
        );

        if ($this->helper->shouldSkipValidation($value, $constraint)) {
            return;
        }

        $this->validateInitials((string) $value);
    }

    private function validateInitials(string $value): void
    {
        if ($value === '') {
            return;
        }

        if ($this->helper->isOnlyWhitespace($value)) {
            $this->helper->addViolation('initials.spaces');
        }
    }
}
