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
    private const RULES = [
        'password.invalid.length' => 'isValidLength',
        'password.missing.uppercase' => 'hasUppercase',
        'password.missing.number' => 'hasNumber',
    ];

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->isNull($value)) {
            return;
        }

        if ($constraint->isOptional() && $this->isEmpty($value)) {
            return;
        }

        foreach (self::RULES as $message => $method) {
            if (!$this->{$method}($value)) {
                $this->addViolation($this->translator->trans($message));
            }
        }
    }

    private function isValidLength(mixed $value): bool
    {
        $length = strlen($value);
        return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
    }

    private function hasNumber(mixed $value): bool
    {
        return (bool) preg_match('/[0-9]/', $value);
    }

    private function hasUppercase(mixed $value): bool
    {
        return (bool) preg_match('/[A-Z]/', $value);
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
