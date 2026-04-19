<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Normalizer;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;

use function filter_var;
use function is_bool;
use function is_int;
use function is_string;
use function trim;

final class BooleanNormalizer
{
    public function normalize(mixed $value): ?bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_int($value) => $this->normalizeInteger($value),
            is_string($value) => $this->normalizeString($value),
            default => null,
        };
    }

    private function normalizeInteger(int $value): ?bool
    {
        return match ($value) {
            0 => false,
            1 => true,
            default => null,
        };
    }

    private function normalizeString(string $value): ?bool
    {
        $normalizedValue = trim($value);

        if ($normalizedValue === '') {
            return null;
        }

        $normalized = filter_var(
            $normalizedValue,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        return is_bool($normalized) ? $normalized : null;
    }
}
