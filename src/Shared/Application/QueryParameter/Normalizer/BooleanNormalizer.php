<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Normalizer;

use function is_bool;
use function is_int;
use function is_string;
use function strtolower;
use function trim;

final class BooleanNormalizer
{
    public function normalize(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                0 => false,
                1 => true,
                default => null,
            };
        }

        if (!is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '0', 'false' => false,
            '1', 'true' => true,
            default => null,
        };
    }
}
