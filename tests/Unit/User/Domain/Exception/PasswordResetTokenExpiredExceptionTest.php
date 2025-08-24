<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;

final class PasswordResetTokenExpiredExceptionTest extends UnitTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new PasswordResetTokenExpiredException();

        $this->assertSame('Password reset token has expired', $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $exception = new PasswordResetTokenExpiredException();

        $this->assertSame(0, $exception->getCode());
    }
}
