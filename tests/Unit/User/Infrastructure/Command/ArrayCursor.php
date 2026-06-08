<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

use Iterator;
use MongoDB\BSON\Int64;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Server;
use RuntimeException;

/**
 * @psalm-type TestDocumentValue = object|array|string|int|float|bool|null
 * @psalm-type TestDocument = array<string, TestDocumentValue>
 *
 * @implements CursorInterface<int, TestDocument>
 * @implements Iterator<int, TestDocument>
 */
final class ArrayCursor implements CursorInterface, Iterator
{
    private int $position = 0;

    /** @param list<TestDocument> $documents */
    public function __construct(private readonly array $documents)
    {
    }

    #[\Override]
    public function current(): object|array|null
    {
        return $this->documents[$this->position] ?? null;
    }

    #[\Override]
    public function key(): ?int
    {
        return $this->valid() ? $this->position : null;
    }

    #[\Override]
    public function next(): void
    {
        ++$this->position;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\Override]
    public function valid(): bool
    {
        return isset($this->documents[$this->position]);
    }

    #[\Override]
    public function getId(): Int64
    {
        throw new RuntimeException('Cursor id is not available in tests.');
    }

    #[\Override]
    public function getServer(): Server
    {
        throw new RuntimeException('Cursor server is not available in tests.');
    }

    /** @param array<string, string|array<string, string>> $typemap */
    #[\Override]
    public function setTypeMap(array $typemap): void
    {
    }

    #[\Override]
    public function isDead(): bool
    {
        return !$this->valid();
    }

    /**
     * @return list<TestDocument>
     */
    #[\Override]
    public function toArray(): array
    {
        return $this->documents;
    }
}
