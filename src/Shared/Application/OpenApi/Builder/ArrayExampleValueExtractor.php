<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ArrayExampleValueExtractor
{
    public function extract(mixed $example): array|string|int|bool|null
    {
        if (!is_array($example)) {
            return null;
        }

        if ($example === []) {
            return null;
        }

        if (array_is_list($example)) {
            return $example[0] ?? null;
        }

        $firstKey = array_key_first($example);

        return $firstKey === null ? null : $example[$firstKey];
    }
}
