<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\Tests\Unit\UnitTestCase;

final class UnverifiedProviderEmailExceptionTest extends UnitTestCase
{
    public function testMessageIncludesProviderName(): void
    {
        $provider = $this->faker->word();
        $exception = new UnverifiedProviderEmailException($provider);

        $this->assertSame(
            sprintf(
                'OAuth provider %s did not return a verified email',
                $provider,
            ),
            $exception->getMessage()
        );
    }
}
