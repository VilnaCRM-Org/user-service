<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

use App\Tests\Unit\UnitTestCase;
use MongoDB\BSON\Int64;
use MongoDB\Driver\Server;
use RuntimeException;

final class ArrayCursorTest extends UnitTestCase
{
    public function testReportsDeadStateForEmptyDocuments(): void
    {
        $cursor = new ArrayCursor([]);

        $cursor->setTypeMap([]);

        $this->assertTrue($cursor->isDead());
        $this->assertSame([], $cursor->toArray());
    }

    public function testIteratesDocumentsInOrderWithSequentialKeys(): void
    {
        $documents = [
            ['_id' => 1, 'email' => 'first@example.com'],
            ['_id' => 2, 'email' => 'second@example.com'],
            ['_id' => 3, 'email' => 'third@example.com'],
        ];
        $cursor = new ArrayCursor($documents);

        $collected = [];
        $keys = [];
        foreach ($cursor as $key => $document) {
            $keys[] = $key;
            $collected[] = $document;
        }

        $this->assertSame([0, 1, 2], $keys);
        $this->assertSame($documents, $collected);
        $this->assertNull($cursor->key());
        $this->assertNull($cursor->current());
        $this->assertTrue($cursor->isDead());
    }

    public function testSetTypeMapIsNoOpAndPreservesDocuments(): void
    {
        $documents = [['_id' => 1, 'email' => 'noop@example.com']];
        $cursor = new ArrayCursor($documents);

        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);

        $this->assertSame($documents, $cursor->toArray());
        $this->assertSame(0, $cursor->key());
        $this->assertSame($documents[0], $cursor->current());
    }

    public function testIdMetadataIsUnavailable(): void
    {
        $this->expectException(RuntimeException::class);

        self::assertInstanceOf(Int64::class, (new ArrayCursor([]))->getId());
    }

    public function testServerMetadataIsUnavailable(): void
    {
        $this->expectException(RuntimeException::class);

        self::assertInstanceOf(Server::class, (new ArrayCursor([]))->getServer());
    }
}
