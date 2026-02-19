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
        if (is_int($value)) {
            return $this->normalizeInt($value);
        }

        if (is_string($value)) {
            return $this->normalizeString($value);
        }

        return null;
    }

    /**
     * @psalm-return int<1, max>|null
     */
    private function normalizeInt(int $value): ?int
    {
        if ($value < 1) {
            return null;
        }

        return $value;
    }

    /**
     * @psalm-return int<1, max>|null
     */
    private function normalizeString(string $value): ?int
    {
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return $this->normalizeInt((int) $value);
    }
}
