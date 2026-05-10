<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;

final class PasswordResetTokenAlreadyUsedExceptionTest extends UnitTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new PasswordResetTokenAlreadyUsedException();

        $this->assertSame('Password reset token has already been used', $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $exception = new PasswordResetTokenAlreadyUsedException();

        $this->assertSame(0, $exception->getCode());
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new PasswordResetTokenAlreadyUsedException();

        $this->assertSame(
            'error.password-reset-token-already-used',
            $exception->getTranslationTemplate()
        );
    }
}
