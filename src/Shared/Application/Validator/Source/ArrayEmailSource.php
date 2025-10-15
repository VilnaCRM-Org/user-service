<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Source;

final class ArrayEmailSource implements BatchEmailSource
{
    public function extract(mixed $entry): ?string
    {
        if (! is_array($entry)) {
            return null;
        }

        $email = $entry['email'] ?? null;

        if (! is_string($email)) {
            return null;
        }

        return $email;
    }
}
