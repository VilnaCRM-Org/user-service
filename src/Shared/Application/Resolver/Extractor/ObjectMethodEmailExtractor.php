<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Extractor;

final class ObjectMethodEmailExtractor implements BatchEmailExtractor
{
    public function __construct(private readonly string $method)
    {
    }

    /**
     * @param array<string, string|null>|int|object|string|null $entry
     */
    #[\Override]
    public function extract(mixed $entry): ?string
    {
        if (!is_object($entry)) {
            return null;
        }

        if (!is_callable([$entry, $this->method])) {
            return null;
        }

        $value = $entry->{$this->method}();

        if (!is_string($value)) {
            return null;
        }

        return $value;
    }
}
