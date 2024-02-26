<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\UserNotFoundException;

class UserNotFoundExceptionTest extends UnitTestCase
{
    public function testMessage(): void
    {
        $exception = new UserNotFoundException();

        $this->assertEquals('User not found', $exception->getMessage());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue((new UserNotFoundException()) instanceof \RuntimeException);
    }
}
