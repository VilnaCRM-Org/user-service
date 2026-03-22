<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Extractor;

final class ArrayExampleValueExtractor
{
    /**
     * @param array<string|array<true>>|string $example
     *
     * @psalm-param 'string'|array{0?: 'first', 1?: 'second', a?: array{nested: true}} $example
     */
    public function extract(array|string $example): array|string|int|bool|null
    {
        if (!is_array($example)) {
            return null;
        }

        return array_is_list($example)
            ? ($example[0] ?? null)
            : (array_values($example)[0] ?? null);
    }
}
