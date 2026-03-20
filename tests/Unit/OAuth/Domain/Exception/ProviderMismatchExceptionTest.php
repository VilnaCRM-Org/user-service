<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\ProviderMismatchException;
use App\Tests\Unit\UnitTestCase;

final class ProviderMismatchExceptionTest extends UnitTestCase
{
    public function testMessageIncludesProviders(): void
    {
        $exception = new ProviderMismatchException('github', 'google');

        $this->assertSame(
            'Provider mismatch: expected github, got google',
            $exception->getMessage()
        );
    }
}
