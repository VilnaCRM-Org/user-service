<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Source;

final class ChainEmailSource implements BatchEmailSource
{
    public function __construct(
        private readonly BatchEmailSource $primaryEmailSource,
        private readonly BatchEmailSource $fallbackEmailSource
    ) {
    }

    /**
     * @param array<string, mixed> $entry
     */
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        return $this->primaryEmailSource->extract($entry)
            ?? $this->fallbackEmailSource->extract($entry);
    }
}
