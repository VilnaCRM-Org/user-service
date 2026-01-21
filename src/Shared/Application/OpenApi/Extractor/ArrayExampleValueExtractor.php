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
        return match (true) {
            !is_array($example),
            $example === [] => null,
            array_is_list($example) => $example[0] ?? null,
            default => reset($example),
        };
    }
}
