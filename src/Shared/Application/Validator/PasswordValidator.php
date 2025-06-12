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

        $password = (string) $value;
        $this->validatePassword($password, $helper);
    }

    private function validatePassword(
        string $password,
        BaseValidationHelper $helper
    ): void {
        $this->validateLength($password, $helper);
        $this->validateNumbers($password, $helper);
        $this->validateUppercase($password, $helper);
    }

    private function validateLength(
        string $password,
        BaseValidationHelper $helper
    ): void {
        $isInvalid = $helper->isLengthInvalid(
            $password,
            self::MIN_LENGTH,
            self::MAX_LENGTH
        );

        if ($isInvalid) {
            $helper->addViolation('password.invalid.length');
        }
    }

    private function validateNumbers(
        string $password,
        BaseValidationHelper $helper
    ): void {
        if ($helper->hasNoNumbers($password)) {
            $helper->addViolation('password.missing.number');
        }
    }

    private function validateUppercase(
        string $password,
        BaseValidationHelper $helper
    ): void {
        if ($helper->hasNoUppercase($password)) {
            $helper->addViolation('password.missing.uppercase');
        }
    }
}
