<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Normalizer;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;

use function filter_var;
use function is_bool;
use function is_string;
use function strtolower;
use function trim;

final class BooleanNormalizer
{
    public function normalize(mixed $value): ?bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_string($value) => $this->normalizeString($value),
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

        if (!is_bool($normalized)) {
            return null;
        }

        return match (strtolower($normalizedValue)) {
            'true', 'false' => $normalized,
            default => null,
        };
    }
}
