<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Source;

final class NullEmailSource implements BatchEmailSource
{
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        return null;
    }
}
