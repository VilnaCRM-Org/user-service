<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Source;

final class ObjectPropertyEmailSource implements BatchEmailSource
{
    public function __construct(private readonly string $property)
    {
    }

    public function extract(mixed $entry): ?string
    {
        if (! is_object($entry)) {
            return null;
        }

        $value = $entry->{$this->property} ?? null;

        if (! is_string($value)) {
            return null;
        }

        return $value;
    }
}
