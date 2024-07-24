<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\UserIsNotConfirmedException;

final class UserIsNotConfirmedExceptionTest extends UnitTestCase
{
    public function testMessage(): void
    {
        $exception = new UserIsNotConfirmedException();

        $this->assertEquals('User is not confirmed', $exception->getMessage());
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new UserIsNotConfirmedException();

        $this->assertEquals(
            'error.user-is-not-confirmed',
            $exception->getTranslationTemplate()
        );
    }

    public function testGetTranslationArgs(): void
    {
        $exception = new UserIsNotConfirmedException();

        $this->assertEquals([], $exception->getTranslationArgs());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertInstanceOf(DomainException::class, (new UserIsNotConfirmedException()));
    }
}
