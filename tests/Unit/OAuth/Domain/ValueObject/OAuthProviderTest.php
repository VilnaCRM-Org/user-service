<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\ValueObject;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;

final class OAuthProviderTest extends UnitTestCase
{
    public function testValueIsStored(): void
    {
        $provider = new OAuthProvider('github');

        $this->assertSame('github', $provider->value);
    }

    public function testToStringReturnsValue(): void
    {
        $provider = new OAuthProvider('google');

        $this->assertSame('google', (string) $provider);
    }
}
