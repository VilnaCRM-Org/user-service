<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;

final class UuidTest extends UnitTestCase
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
        $additionalChars = 'aa';
        $uuidString = $this->faker->uuid().$additionalChars;
        $uuid = new Uuid($uuidString);

        $expectedBinary = hex2bin(
            str_replace('-', '', $uuidString)
        );

        $this->assertSame($expectedBinary, $uuid->toBinary());
    }

    public function testToBinaryNotConvertible(): void
    {
        $additionalChar = 'a';
        $uuidString = $this->faker->uuid().$additionalChar;
        $uuid = new Uuid($uuidString);

        $this->assertNull($uuid->toBinary());
    }

    public function testToBinaryRemovesDashes(): void
    {
        // Use a fixed UUID to verify dash removal
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = new Uuid($uuidString);

        $binary = $uuid->toBinary();

        // Verify the binary representation matches UUID without dashes
        $expectedBinary = hex2bin('550e8400e29b41d4a716446655440000');

        $this->assertSame($expectedBinary, $binary);
        $this->assertNotNull($binary);
    }

    public function testToBinaryReturnsNullForOddLengthString(): void
    {
        // Single character - odd length
        $uuid = new Uuid('a');

        $this->assertNull($this->withoutPhpWarnings(fn () => $uuid->toBinary()));
    }

    public function testToBinaryReturnsNullForEmptyString(): void
    {
        $uuid = new Uuid('');

        $this->assertNull($this->withoutPhpWarnings(fn () => $uuid->toBinary()));
    }

    public function testToBinaryWorksForEvenLengthNonUuidString(): void
    {
        // Even length hex string (4 characters = 2 bytes)
        $uuid = new Uuid('abcd');

        $binary = $uuid->toBinary();

        $this->assertNotNull($binary);
        $this->assertSame(hex2bin('abcd'), $binary);
        $this->assertSame(2, strlen($binary));
    }

    public function testIsConvertableToBinaryReturnsFalseForOddLength(): void
    {
        // Create a string with odd length that would fail hex2bin
        $oddLengthString = 'abc'; // 3 characters

        $uuid = new Uuid($oddLengthString);

        // For odd-length strings, toBinary should return null
        $this->assertNull($uuid->toBinary());
    }

    public function testIsConvertableToBinaryReturnsTrueForEvenLength(): void
    {
        // Create a string with even length that should pass hex2bin
        $evenLengthString = 'abcd'; // 4 characters

        $uuid = new Uuid($evenLengthString);

        // For even-length hex strings, toBinary should not return null
        $this->assertNotNull($uuid->toBinary());
    }

    public function testToBinaryEarlyReturnsNullForOddLength(): void
    {
        // Test with a string that would cause hex2bin to fail
        // but should be caught by the isConvertableToBinary check
        $uuid = new Uuid('abc'); // 3 characters - odd length

        $result = $this->withoutPhpWarnings(fn () => $uuid->toBinary());

        // Should return null because of odd length, not because hex2bin failed
        $this->assertNull($result);
    }

    public function testToBinaryOddLengthDoesNotTriggerHexWarning(): void
    {
        $oddHex = $this->faker->regexify('[a-f0-9]{7}');
        $uuid = new Uuid($oddHex);

        $result = $this->withoutPhpWarnings(fn () => $uuid->toBinary());

        $this->assertNull($result);
    }

    public function testToBinaryReturnsNullForInvalidHex(): void
    {
        // Even length but invalid hex characters
        $uuid = new Uuid('ghij'); // 4 characters, even length, but invalid hex

        $result = $this->withoutPhpWarnings(fn () => $uuid->toBinary());

        // hex2bin should fail and return null
        $this->assertNull($result);
    }

    public function testToBinarySucceedsForValidHex(): void
    {
        // Valid hex string with even length
        $uuid = new Uuid('1234'); // 4 characters, valid hex

        $result = $uuid->toBinary();

        $this->assertNotNull($result);
        $this->assertSame("\x12\x34", $result);
    }
}
