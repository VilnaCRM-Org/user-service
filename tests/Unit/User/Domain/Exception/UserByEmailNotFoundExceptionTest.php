<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\UserByEmailNotFoundException;

final class UserByEmailNotFoundExceptionTest extends UnitTestCase
{
    public function testCreateException(): void
    {
        $email = 'example@email.com';
        $exception = new UserByEmailNotFoundException($email);

        $this->assertEquals(
            "User with email {$email} not found",
            $exception->getMessage()
        );
    }

    public function testGetTranslationTemplate(): void
    {
        $email = 'example@email.com';
        $exception = new UserByEmailNotFoundException($email);

        $this->assertEquals(
            'error.user-by-email-not-found',
            $exception->getTranslationTemplate()
        );
    }

    public function testGetTranslationArgs(): void
    {
        $email = 'example@email.com';
        $exception = new UserByEmailNotFoundException($email);

        $this->assertEquals(
            ['email' => $email],
            $exception->getTranslationArgs()
        );
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertInstanceOf(
            DomainException::class,
            (new UserByEmailNotFoundException('example@email.com'))
        );
    }
}
