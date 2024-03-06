<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\TokenNotFoundException;

class TokenNotFoundExceptionTest extends UnitTestCase
{
    public function testMessage(): void
    {
        $exception = new TokenNotFoundException();

        $this->assertEquals('Token not found', $exception->getMessage());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue((new TokenNotFoundException()) instanceof DomainException);
    }
}
