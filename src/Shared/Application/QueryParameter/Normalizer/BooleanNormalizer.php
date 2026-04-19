<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Normalizer;

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
        return match (strtolower(trim($value))) {
            'true' => true,
            'false' => false,
            default => null,
        };
    }
}
