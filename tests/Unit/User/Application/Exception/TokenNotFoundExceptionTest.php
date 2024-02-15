<?php

namespace App\Tests\Unit\User\Application\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Exception\TokenNotFoundException;

class TokenNotFoundExceptionTest extends UnitTestCase
{
    public function testMessage(): void
    {
        $exception = new TokenNotFoundException();

        $this->assertEquals('Token not found', $exception->getMessage());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue((new TokenNotFoundException()) instanceof \RuntimeException);
    }
}
