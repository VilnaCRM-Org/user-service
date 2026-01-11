<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Source;

final class ObjectPropertyEmailSource implements BatchEmailSource
{
    public function __construct(private readonly string $property)
    {
    }

    #[\Override]
    public function extract(mixed $entry): ?string
    {
        if (! is_object($entry)) {
            return null;
        }

        return $this->extractFromObject($entry);
    }

    private function extractFromObject(object $entry): ?string
    {
        $value = $entry->{$this->property} ?? null;

        if (! is_string($value)) {
            return null;
        }

        return $value;
    }
}
