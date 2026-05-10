<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

final class EmptyValueValidator
{
    /**
     * @psalm-param ''|'a'|null $value
     */
    public function isEmpty(?string $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * @param array<string, scalar|null>|string|int|float|bool|null $value
     */
    public function shouldSkip(
        array|string|int|float|bool|null $value,
        Constraint $_constraint
    ): bool {
        return $value === null;
    }
}
