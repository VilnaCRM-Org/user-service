<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\PasswordResetTokenMismatchException;

final class PasswordResetTokenMismatchExceptionTest extends UnitTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new PasswordResetTokenMismatchException();

        $this->assertSame(
            'Password reset token does not belong to the specified user',
            $exception->getMessage()
        );
    }

    public function testExceptionCode(): void
    {
        $exception = new PasswordResetTokenMismatchException();

        $this->assertSame(0, $exception->getCode());
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new PasswordResetTokenMismatchException();

        $this->assertSame(
            'error.password-reset-token-mismatch',
            $exception->getTranslationTemplate()
        );
    }
}
