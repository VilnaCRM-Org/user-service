<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\OAuthProviderException;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;

final class OAuthProviderExceptionTest extends UnitTestCase
{
    public function testMessageIncludesProviderAndDetail(): void
    {
        $provider = $this->faker->word();
        $message = $this->faker->sentence();

        $exception = new OAuthProviderException($provider, $message);

        $this->assertSame(
            sprintf('OAuth provider %s error: %s', $provider, $message),
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $provider = $this->faker->word();
        $message = $this->faker->sentence();
        $previous = new RuntimeException($this->faker->sentence());

        $exception = new OAuthProviderException(
            $provider,
            $message,
            $previous,
        );

        $this->assertSame($previous, $exception->getPrevious());
    }
}
