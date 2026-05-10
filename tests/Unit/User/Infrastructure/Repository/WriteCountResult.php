<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class WriteCountResult
{
    public function __construct(private readonly int|string $count)
    {
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getModifiedCount(): int|string
    {
        return $this->count;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getDeletedCount(): int|string
    {
        return $this->count;
    }
}
