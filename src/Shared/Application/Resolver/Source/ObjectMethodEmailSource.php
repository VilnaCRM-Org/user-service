<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Source;

final class ObjectMethodEmailSource implements BatchEmailSource
{
    public function __construct(private readonly string $method)
    {
    }

    /**
     * @param array|int|null|object|string $entry
     *
     * @psalm-param 123|array|null|object|string $entry
     */
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        if (!is_object($entry)) {
            return null;
        }

        return match (true) {
            !is_callable([$entry, $this->method]) => null,
            !is_string($value = $entry->{$this->method}()) => null,
            default => $value,
        };
    }
}
