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
