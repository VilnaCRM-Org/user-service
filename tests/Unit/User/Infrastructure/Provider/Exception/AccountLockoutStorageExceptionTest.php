<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Provider\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Provider\Exception\AccountLockoutStorageException;
use RuntimeException;

final class AccountLockoutStorageExceptionTest extends UnitTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new AccountLockoutStorageException();

        $this->assertSame(
            'Account lockout storage is unavailable; sign-in attempt refused.',
            $exception->getMessage()
        );
    }

    public function testExceptionCode(): void
    {
        $exception = new AccountLockoutStorageException();

        $this->assertSame(0, $exception->getCode());
    }

    public function testExceptionIsRuntimeException(): void
    {
        $exception = new AccountLockoutStorageException();

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }
}
