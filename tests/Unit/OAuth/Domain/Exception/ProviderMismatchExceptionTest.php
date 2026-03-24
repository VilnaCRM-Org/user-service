<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\ProviderMismatchException;
use App\Tests\Unit\UnitTestCase;

final class ProviderMismatchExceptionTest extends UnitTestCase
{
    public function testMessageIncludesProviders(): void
    {
        $expected = $this->faker->word();
        $actual = $this->faker->word();

        $exception = new ProviderMismatchException($expected, $actual);

        $this->assertSame(
            sprintf('Provider mismatch: expected %s, got %s', $expected, $actual),
            $exception->getMessage()
        );
    }
}
