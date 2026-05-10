<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

final class MongoDBWriteResultCounter
{
    /**
     * @psalm-return int<0, max>
     */
    public function modifiedDocumentCount(mixed $result): int
    {
        return $this->normalizeCount(
            is_int($result) ? $result : $this->objectModifiedDocumentCount($result)
        );
    }

    /**
     * @psalm-return int<0, max>
     */
    public function removedDocumentCount(mixed $result): int
    {
        return $this->normalizeCount(
            is_int($result) ? $result : $this->objectDeletedDocumentCount($result)
        );
    }

    public function wasDocumentUpdated(mixed $result): bool
    {
        return $this->modifiedDocumentCount($result) > 0;
    }

    private function objectModifiedDocumentCount(mixed $result): int
    {
        $count = 0;

        if (is_object($result) && method_exists($result, 'getModifiedCount')) {
            $candidate = $result->getModifiedCount();
            $count = is_int($candidate) ? $candidate : 0;
        }

        return $count;
    }

    private function objectDeletedDocumentCount(mixed $result): int
    {
        $count = 0;

        if (is_object($result) && method_exists($result, 'getDeletedCount')) {
            $candidate = $result->getDeletedCount();
            $count = is_int($candidate) ? $candidate : 0;
        }

        return $count;
    }

    /**
     * @psalm-return int<0, max>
     */
    private function normalizeCount(int $count): int
    {
        return max(0, $count);
    }
}
