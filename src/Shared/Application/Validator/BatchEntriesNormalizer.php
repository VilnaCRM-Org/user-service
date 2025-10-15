<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

final class BatchEntriesNormalizer
{
    public function normalize(mixed $value): BatchEntriesResult
    {
        $entries = null;

        return match (true) {
            !is_iterable($value) => $this->notIterableResult(),
            ($entries = $this->toArray($value)) === [] => $this->emptyResult(),
            default => new BatchEntriesResult(BatchEntriesResult::STATE_VALID, $entries),
        };
    }

    /**
     * @param iterable<array-key, array|object|string|int|float|bool|null> $value
     *
     * @return array<int, array|object|string|int|float|bool|null>
     */
    private function toArray(iterable $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

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
