<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;

final class PasswordResetTokenNotFoundExceptionTest extends UnitTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new PasswordResetTokenNotFoundException();

        $this->assertSame('Password reset token not found', $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $exception = new PasswordResetTokenNotFoundException();

        $this->assertSame(0, $exception->getCode());
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new PasswordResetTokenNotFoundException();

        $this->assertSame(
            'error.password-reset-token-not-found',
            $exception->getTranslationTemplate()
        );
    }
}
