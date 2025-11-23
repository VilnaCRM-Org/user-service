<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Source;

final class NullEmailSource implements BatchEmailSource
{
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        return null;
    }
}
