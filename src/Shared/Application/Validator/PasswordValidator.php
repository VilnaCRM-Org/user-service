<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordValidator extends ConstraintValidator
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 64;

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

        $password = (string) $value;
        $this->validatePassword($password);
    }

    private function validatePassword(string $password): void
    {
        $this->validateLength($password);
        $this->validateNumbers($password);
        $this->validateUppercase($password);
    }

    private function validateLength(string $password): void
    {
        $isInvalid = $this->helper->isLengthInvalid(
            $password,
            self::MIN_LENGTH,
            self::MAX_LENGTH
        );

        if ($isInvalid) {
            $this->helper->addViolation('password.invalid.length');
        }
    }

    private function validateNumbers(string $password): void
    {
        if ($this->helper->hasNoNumbers($password)) {
            $this->helper->addViolation('password.missing.number');
        }
    }

    private function validateUppercase(string $password): void
    {
        if ($this->helper->hasNoUppercase($password)) {
            $this->helper->addViolation('password.missing.uppercase');
        }
    }
}
