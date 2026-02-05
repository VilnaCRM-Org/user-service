<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;

final class UuidBinarySuccessTest extends UnitTestCase
{
    public function testToBinaryConvertible(): void
    {
        $uuidString = $this->faker->uuid();
        $uuid = new Uuid($uuidString);

        $expectedBinary = hex2bin(
            str_replace('-', '', $uuidString)
        );

        $this->assertSame($expectedBinary, $uuid->toBinary());
    }

    public function testToBinaryConvertibleWithNonDefaultLength(): void
    {
        $additionalChars = $this->faker->regexify('[a-f0-9]{2}');
        $uuidString = $this->faker->uuid().$additionalChars;
        $uuid = new Uuid($uuidString);

        $expectedBinary = hex2bin(
            str_replace('-', '', $uuidString)
        );

        $this->assertSame($expectedBinary, $uuid->toBinary());
    }

    public function testToBinaryRemovesDashes(): void
    {
        $uuidString = $this->faker->uuid();
        $uuid = new Uuid($uuidString);

        $binary = $uuid->toBinary();
        $expectedBinary = hex2bin(str_replace('-', '', $uuidString));

        $this->assertSame($expectedBinary, $binary);
        $this->assertNotNull($binary);
    }

    public function testToBinaryWorksForEvenLengthNonUuidString(): void
    {
        $hex = $this->faker->regexify('[a-f0-9]{4}');
        $uuid = new Uuid($hex);

        $binary = $uuid->toBinary();

        $this->assertNotNull($binary);
        $this->assertSame(hex2bin($hex), $binary);
        $this->assertSame(2, strlen($binary));
    }

    public function testIsConvertableToBinaryReturnsTrueForEvenLength(): void
    {
        $evenLengthString = $this->faker->regexify('[a-f0-9]{4}');

        $uuid = new Uuid($evenLengthString);

        $this->assertNotNull($uuid->toBinary());
    }

    public function testToBinarySucceedsForValidHex(): void
    {
        $hex = $this->faker->regexify('[a-f0-9]{4}');
        $uuid = new Uuid($hex);

        $result = $uuid->toBinary();

        $this->assertNotNull($result);
        $this->assertSame(hex2bin($hex), $result);
    }
}
