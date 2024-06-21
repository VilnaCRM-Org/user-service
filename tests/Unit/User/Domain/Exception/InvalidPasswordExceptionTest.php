<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\InvalidPasswordException;

final class InvalidPasswordExceptionTest extends UnitTestCase
{
    public function testMessage(): void
    {
        $exception = new InvalidPasswordException();

        $this->assertEquals(
            'Old password is invalid',
            $exception->getMessage()
        );
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new InvalidPasswordException();

        $this->assertEquals(
            'error.invalid-password',
            $exception->getTranslationTemplate()
        );
    }

    public function testGetTranslationArgs(): void
    {
        $exception = new InvalidPasswordException();

        $this->assertEquals([], $exception->getTranslationArgs());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue(
            (new InvalidPasswordException()) instanceof DomainException
        );
    }
}
