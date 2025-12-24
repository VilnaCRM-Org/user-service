<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use App\Shared\Application\Resolver\Source\ArrayEmailSource;
use App\Shared\Application\Resolver\Source\BatchEmailSource;
use App\Shared\Application\Resolver\Source\ObjectMethodEmailSource;
use App\Shared\Application\Resolver\Source\ObjectPropertyEmailSource;

final class BatchEmailResolver
{
    /**
     * @var array<BatchEmailSource>
     */
    private readonly array $sources;

    /**
     * @param array<BatchEmailSource> $sources
     */
    public function __construct(array $sources = [])
    {
        $this->sources = $sources !== [] ? $sources : [
            new ArrayEmailSource(),
            new ObjectMethodEmailSource('getEmail'),
            new ObjectPropertyEmailSource('email'),
        ];
    }

    public function resolve(mixed $entry): ?string
    {
        $candidates = array_filter(
            array_map(
                static fn (BatchEmailSource $source): string => trim(
                    (string) $source->extract($entry)
                ),
                $this->sources
            ),
            static fn (string $candidate): bool => $candidate !== ''
        );

        $first = reset($candidates);

        if ($first === false) {
            return null;
        }

        return mb_strtolower($first);
    }
}
