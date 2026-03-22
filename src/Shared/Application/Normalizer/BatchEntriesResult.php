<?php

declare(strict_types=1);

namespace App\Shared\Application\Normalizer;

final class BatchEntriesResult
{
    public const STATE_NOT_ITERABLE = 'not_iterable';
    public const STATE_EMPTY = 'empty';
    public const STATE_VALID = 'valid';

    /**
     * @param array<int, array|object|string|int|float|bool|null> $entries
     */
    public function __construct(
        private readonly string $state,
        private readonly array $entries
    ) {
    }

    public function state(): string
    {
        return $this->state;
    }

    /**
     * @return array<int, array|object|string|int|float|bool|null>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
