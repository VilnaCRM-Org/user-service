<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use App\Shared\Application\Resolver\Extractor\ArrayEmailExtractor;
use App\Shared\Application\Resolver\Extractor\BatchEmailExtractor;
use App\Shared\Application\Resolver\Extractor\ObjectMethodEmailExtractor;
use App\Shared\Application\Resolver\Extractor\ObjectPropertyEmailExtractor;

final class BatchEmailResolver
{
    /**
     * @var array<BatchEmailExtractor>
     */
    private readonly array $sources;

    /**
     * @param array<BatchEmailExtractor> $sources
     */
    public function __construct(array $sources = [])
    {
        $this->sources = $sources !== [] ? $sources : [
            new ArrayEmailExtractor(),
            new ObjectMethodEmailExtractor('getEmail'),
            new ObjectPropertyEmailExtractor('email'),
        ];
    }

    /**
     * @param array<string|null> $entry
     *
     * @psalm-param array{email?: 'ÜSER@Example.com'|null} $entry
     */
    public function resolve(array $entry): ?string
    {
        $candidates = array_filter(
            array_map(
                static fn (BatchEmailExtractor $source): string => trim(
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
