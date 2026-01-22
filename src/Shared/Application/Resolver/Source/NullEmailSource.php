<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Source;

final class NullEmailSource implements BatchEmailSource
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
