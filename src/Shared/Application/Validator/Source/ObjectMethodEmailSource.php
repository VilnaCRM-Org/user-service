<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Source;

final class ObjectMethodEmailSource implements BatchEmailSource
{
    public function __construct(private readonly string $method)
    {
    }

    #[\Override]
    public function extract(mixed $entry): ?string
    {
        return match (true) {
            !is_callable([$entry, $this->method]) => null,
            !is_string($value = $entry->{$this->method}()) => null,
            default => $value,
        };
    }
}
