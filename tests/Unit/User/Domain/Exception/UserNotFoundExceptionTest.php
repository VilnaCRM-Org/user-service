<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\UserNotFoundException;

class UserNotFoundExceptionTest extends UnitTestCase
{
    public function testMessage(): void
    {
        $exception = new UserNotFoundException();

        $this->assertEquals('User not found', $exception->getMessage());
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new UserNotFoundException();

        $this->assertEquals('error.user-not-found', $exception->getTranslationTemplate());
    }

    public function testGetTranslationArgs(): void
    {

        $exception = new UserNotFoundException();

        $this->assertEquals([], $exception->getTranslationArgs());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue((new UserNotFoundException()) instanceof DomainException);
    }
}
