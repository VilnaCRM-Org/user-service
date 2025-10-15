<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener\QueryParameter\Pagination;

use function ctype_digit;
use function is_int;
use function is_string;

final class PositiveIntegerNormalizer
{
    public function normalize(mixed $value): ?int
    {
        return match (true) {
            is_int($value) => $this->normalizeInt($value),
            is_string($value) => $this->normalizeString($value),
            default => null,
        };
    }

    private function normalizeInt(int $value): ?int
    {
        return match (true) {
            $value < 1 => null,
            default => $value,
        };
    }

    private function normalizeString(string $value): ?int
    {
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return $this->normalizeInt((int) $value);
    }
}
