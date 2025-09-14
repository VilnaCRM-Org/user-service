<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Strategy;

final readonly class InitialsValidationChecks
{
    public function isOnlySpaces(mixed $value): bool
    {
        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            return false;
        }
        if ($value === '') {
            return false;
        }
        return trim($value) === '';
    }
}
