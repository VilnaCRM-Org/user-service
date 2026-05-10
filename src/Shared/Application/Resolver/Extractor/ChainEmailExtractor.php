<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Extractor;

final class ChainEmailExtractor implements BatchEmailExtractor
{
    public function __construct(
        private readonly BatchEmailExtractor $primaryEmailExtractor,
        private readonly BatchEmailExtractor $fallbackEmailExtractor
    ) {
    }

    /**
     * @param array<string, mixed> $entry
     */
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        return $this->primaryEmailExtractor->extract($entry)
            ?? $this->fallbackEmailExtractor->extract($entry);
    }
}
