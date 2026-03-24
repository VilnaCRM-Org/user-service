<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\ValueObject;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;

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
}
