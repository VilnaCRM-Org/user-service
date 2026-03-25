<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Validator;

use function is_array;
use function is_string;
use function trim;

final class ExplicitValueValidator
{
    /**
     * @param array<string, scalar|null>|scalar|null $value
     */
    public function isExplicitlyProvided(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }

    /**
     * @param array<string, scalar|null>|scalar|null $value
     */
    public function wasParameterSent(mixed $value): bool
    {
        return $value !== null;
    }
}
