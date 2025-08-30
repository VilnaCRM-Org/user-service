<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Strategy;

final readonly class InitialsValidationChecks
{
    public function isOnlySpaces(mixed $value): bool
    {
        $trimmedValue = trim($value);
        return $trimmedValue === '' && strlen($value) > 0;
    }
}
