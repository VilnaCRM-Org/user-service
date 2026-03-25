<?php

declare(strict_types=1);

namespace App\Shared\Application\Normalizer;

use App\Shared\Application\DTO\BatchEntriesResult;
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
        if (!is_iterable($value)) {
            return $this->notIterableResult();
        }

        $entries = $this->toArray($value);

        if ($entries === []) {
            return $this->emptyResult();
        }

        return new BatchEntriesResult(BatchEntriesResult::STATE_VALID, $entries);
    }

    /**
     * @param iterable<array-key, array|object|string|int|float|bool|null> $value
     *
     * @return array<array|object|scalar|null>
     *
     * @psalm-return list<array|object|scalar|null>
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
     * @return array<array|object|scalar|null>
     *
     * @psalm-return list<array|object|scalar|null>
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
