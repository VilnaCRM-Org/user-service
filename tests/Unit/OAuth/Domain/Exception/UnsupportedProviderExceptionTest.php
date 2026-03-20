<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\UnsupportedProviderException;
use App\Tests\Unit\UnitTestCase;

final class UnsupportedProviderExceptionTest extends UnitTestCase
{
    public function testMessageIncludesProviderName(): void
    {
        $provider = $this->faker->word();
        $exception = new UnsupportedProviderException($provider);

        $this->assertSame(
            sprintf('Unsupported OAuth provider: %s', $provider),
            $exception->getMessage()
        );
    }
}
