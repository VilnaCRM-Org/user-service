<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Source;

final class ChainEmailSource implements BatchEmailSource
{
    public function __construct(
        private readonly BatchEmailSource $current,
        private readonly BatchEmailSource $next
    ) {
    }

    #[\Override]
    public function extract(mixed $entry): ?string
    {
        return $this->current->extract($entry)
            ?? $this->next->extract($entry);
    }
}
