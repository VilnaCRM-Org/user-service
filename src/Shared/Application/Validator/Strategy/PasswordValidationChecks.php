<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Strategy;

final readonly class PasswordValidationChecks
{
    public function hasValidLength(mixed $value): bool
    {
        $length = strlen($value);
        return $length >= 8 && $length <= 64;
    }

    public function hasNumber(mixed $value): bool
    {
        return preg_match('/[0-9]/', $value) === 1;
    }

    public function hasUppercase(mixed $value): bool
    {
        return preg_match('/[A-Z]/', $value) === 1;
    }
}
