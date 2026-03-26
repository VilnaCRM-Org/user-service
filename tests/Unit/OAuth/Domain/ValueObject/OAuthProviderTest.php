<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\ValueObject;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;

final class OAuthProviderTest extends UnitTestCase
{
    public function testValueIsStored(): void
    {
        $value = $this->faker->word();
        $provider = new OAuthProvider($value);

        $this->assertSame($value, $provider->value);
    }

    public function testToStringReturnsValue(): void
    {
        $value = $this->faker->word();
        $provider = new OAuthProvider($value);

        $this->assertSame($value, (string) $provider);
    }

    public function testThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'OAuth provider cannot be empty'
        );

        new OAuthProvider('');
    }

    public function testThrowsExceptionForWhitespaceOnlyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'OAuth provider cannot be empty'
        );

        new OAuthProvider('   ');
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $value = $this->faker->word();
        $provider1 = new OAuthProvider($value);
        $provider2 = new OAuthProvider($value);

        $this->assertTrue($provider1->equals($provider2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $provider1 = new OAuthProvider($this->faker->word());
        $provider2 = new OAuthProvider(
            $this->faker->word() . $this->faker->word()
        );

        $this->assertFalse($provider1->equals($provider2));
    }

    public function testFromStringCreatesInstance(): void
    {
        $value = $this->faker->word();
        $provider = OAuthProvider::fromString($value);

        $this->assertSame($value, $provider->value);
    }

    public function testFromStringThrowsForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OAuthProvider::fromString('');
    }
}
