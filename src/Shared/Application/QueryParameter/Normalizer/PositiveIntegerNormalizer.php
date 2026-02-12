<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Normalizer;

use function ctype_digit;
use function is_int;
use function is_string;

final class PositiveIntegerNormalizer
{
    public function normalize(string|\stdClass|int $value): ?int
    {
        return match (true) {
            is_int($value) => $this->normalizeInt($value),
            is_string($value) => $this->normalizeString($value),
            default => null,
        };
    }

    /**
     * @psalm-return int<1, max>|null
     */
    private function normalizeInt(int $value): int|null
    {
        return match (true) {
            $value < 1 => null,
            default => $value,
        };
    }

    /**
     * @psalm-return int<1, max>|null
     */
    private function normalizeString(string $value): int|null
    {
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return $this->normalizeInt((int) $value);
    }
}
