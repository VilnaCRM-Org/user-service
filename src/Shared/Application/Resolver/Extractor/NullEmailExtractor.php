<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Extractor;

final class NullEmailExtractor implements BatchEmailExtractor
{
    /**
     * @param array<string> $entry
     *
     * @psalm-param array{email: 'ignored@example.com'} $entry
     */
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        return null;
    }
}
