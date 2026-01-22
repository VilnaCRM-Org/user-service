<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Guard;

use Symfony\Component\Validator\Constraint;

final class EmptyValueGuard
{
    /**
     * @psalm-param ''|'a'|null $value
     */
    public static function isEmpty(?string $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * @psalm-suppress UnusedParam Constraint parameter required for future extension
     */
    public function shouldSkip(
        array|string|int|float|bool|null $value,
        Constraint $constraint
    ): bool {
        return $value === null;
    }
}
