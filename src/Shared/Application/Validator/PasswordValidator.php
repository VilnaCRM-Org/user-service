<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Validator\Strategy\PasswordValidationChecks;
use App\Shared\Application\Validator\Strategy\ValidationSkipChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ValidationSkipChecker $skipChecker =
            new ValidationSkipChecker(),
        private readonly PasswordValidationChecks $validationChecks =
            new PasswordValidationChecks(),
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->skipChecker->shouldSkip($value, $constraint)) {
            return;
        }

        $this->performPasswordValidations($value);
    }

    private function performPasswordValidations(mixed $value): void
    {
        $this->validateLength($value);
        $this->validateUppercase($value);
        $this->validateNumber($value);
    }

    private function validateLength(mixed $value): void
    {
        if (!$this->validationChecks->hasValidLength($value)) {
            $this->addViolation(
                $this->translator->trans('password.invalid.length')
            );
        }
    }

    private function validateNumber(mixed $value): void
    {
        if (!$this->validationChecks->hasNumber($value)) {
            $this->addViolation(
                $this->translator->trans('password.missing.number')
            );
        }
    }

    private function validateUppercase(mixed $value): void
    {
        if (!$this->validationChecks->hasUppercase($value)) {
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
}
