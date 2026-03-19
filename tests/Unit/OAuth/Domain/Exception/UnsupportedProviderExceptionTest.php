<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\UnsupportedProviderException;
use App\Tests\Unit\UnitTestCase;

final class UnsupportedProviderExceptionTest extends UnitTestCase
{
    public function testMessageIncludesProviderName(): void
    {
        $exception = new UnsupportedProviderException('unknown');

        $this->assertSame(
            'Unsupported OAuth provider: unknown',
            $exception->getMessage()
        );
    }
}
