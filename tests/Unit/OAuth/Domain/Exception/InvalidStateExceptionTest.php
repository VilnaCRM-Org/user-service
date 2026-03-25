<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\InvalidStateException;
use App\Tests\Unit\UnitTestCase;

final class InvalidStateExceptionTest extends UnitTestCase
{
    public function testCanBeInstantiatedWithMessage(): void
    {
        $message = $this->faker->sentence();
        $exception = new InvalidStateException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
