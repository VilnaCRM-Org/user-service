<?php

declare(strict_types=1);

namespace App\Shared\Application\Normalizer;

use Traversable;

final class BatchEntriesNormalizer
{
    /**
     * @param \ArrayIterator|string|array<array<string>> $value
     *
     * @psalm-param 'not iterable'|\ArrayIterator<5, array{email: 'alpha@example.com'}>|array{3?: array{email: 'first@example.com'}, 7?: array{email: 'second@example.com'}} $value
     */
    public function normalize(array|\ArrayIterator|string $value): BatchEntriesResult
    {
        return match (true) {
            !is_iterable($value) => $this->notIterableResult(),
            ($entries = $this->toArray($value)) === [] => $this->emptyResult(),
            default => new BatchEntriesResult(BatchEntriesResult::STATE_VALID, $entries),
        };
    }

    /**
     * @param iterable<array-key, array|object|string|int|float|bool|null> $value
     *
     * @return (array|null|object|scalar)[]
     *
     * @psalm-return list<array|null|object|scalar>
     */
    private function toArray(iterable $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        return $this->normalizeTraversable($value);
    }

    /**
     * @param Traversable<array-key, array|object|string|int|float|bool|null> $value
     *
     * @return (array|null|object|scalar)[]
     *
     * @psalm-return list<array|null|object|scalar>
     */
    private function normalizeTraversable(Traversable $value): array
    {
        return iterator_to_array($value, false);
    }

    private function emptyResult(): BatchEntriesResult
    {
        return new BatchEntriesResult(BatchEntriesResult::STATE_EMPTY, []);
    }

    private function notIterableResult(): BatchEntriesResult
    {
        return new BatchEntriesResult(BatchEntriesResult::STATE_NOT_ITERABLE, []);
    }
}
