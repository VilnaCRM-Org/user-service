<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Source;

final class ArrayEmailSource implements BatchEmailSource
{
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        if (! is_array($entry)) {
            return null;
        }

        return $this->extractFromArray($entry);
    }

    /**
     * @param array<string, string|null> $entry
     */
    private function extractFromArray(array $entry): ?string
    {
        $email = $entry['email'] ?? null;

        if (! is_string($email)) {
            return null;
        }

        return $email;
    }
}
