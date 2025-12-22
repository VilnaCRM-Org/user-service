<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Evaluator;

use function is_array;
use function is_string;
use function trim;

final class ExplicitValueEvaluator
{
    public function isExplicitlyProvided(mixed $value): bool
    {
        return match (true) {
            $value === null => false,
            is_string($value) => trim($value) !== '',
            is_array($value) => $value !== [],
            default => true,
        };
    }

    public function wasParameterSent(mixed $value): bool
    {
        return $value !== null;
    }
}
