<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Extractor;

final class ArrayExampleValueExtractor
{
    public function extract(mixed $example): array|string|int|bool|null
    {
        return match (true) {
            !is_array($example),
            $example === [] => null,
            array_is_list($example) => $example[0] ?? null,
            default => reset($example),
        };
    }
}
