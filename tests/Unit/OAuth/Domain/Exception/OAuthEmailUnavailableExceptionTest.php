<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\Tests\Unit\UnitTestCase;

final class OAuthEmailUnavailableExceptionTest extends UnitTestCase
{
    public function testMessageIncludesProviderName(): void
    {
        $provider = $this->faker->word();
        $exception = new OAuthEmailUnavailableException($provider);

        $this->assertSame(
            sprintf(
                'OAuth provider %s did not return an email address',
                $provider,
            ),
            $exception->getMessage()
        );
    }
}
