<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;

final class UuidBinaryFailureTest extends UnitTestCase
{
    public function testToBinaryNotConvertible(): void
    {
        $additionalChar = $this->faker->regexify('[a-f0-9]');
        $uuidString = $this->faker->uuid().$additionalChar;
        $uuid = new Uuid($uuidString);

        $this->assertNull($uuid->toBinary());
    }

    public function testToBinaryReturnsNullForOddLengthString(): void
    {
        $uuid = new Uuid($this->faker->regexify('[a-f0-9]{7}'));

        $this->assertNull($this->withoutPhpWarnings(static fn () => $uuid->toBinary()));
    }

    public function testToBinaryReturnsNullForEmptyString(): void
    {
        $uuid = new Uuid('');

        $this->assertNull($this->withoutPhpWarnings(static fn () => $uuid->toBinary()));
    }

    public function testIsConvertableToBinaryReturnsFalseForOddLength(): void
    {
        $oddLengthString = $this->faker->regexify('[a-f0-9]{3}');

        $uuid = new Uuid($oddLengthString);

        $this->assertNull($uuid->toBinary());
    }

    public function testToBinaryEarlyReturnsNullForOddLength(): void
    {
        $uuid = new Uuid($this->faker->regexify('[a-f0-9]{3}'));

        $result = $this->withoutPhpWarnings(static fn () => $uuid->toBinary());

        $this->assertNull($result);
    }

    public function testToBinaryOddLengthDoesNotTriggerHexWarning(): void
    {
        $oddHex = $this->faker->regexify('[a-f0-9]{7}');
        $uuid = new Uuid($oddHex);

        $result = $this->withoutPhpWarnings(static fn () => $uuid->toBinary());

        $this->assertNull($result);
    }

    public function testToBinaryReturnsNullForInvalidHex(): void
    {
        $invalidHex = $this->faker->regexify('[g-z]{4}');
        $uuid = new Uuid($invalidHex);

        $result = $this->withoutPhpWarnings(static fn () => $uuid->toBinary());

        $this->assertNull($result);
    }
}
