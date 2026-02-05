<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;

final class UuidStringTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $uuidString = $this->faker->uuid();
        $uuid = new Uuid($uuidString);

        $this->assertSame($uuidString, (string) $uuid);
    }

    public function testToString(): void
    {
        $uuidString = $this->faker->uuid();
        $uuid = new Uuid($uuidString);

        $this->assertSame($uuidString, $uuid->__toString());
    }
}
