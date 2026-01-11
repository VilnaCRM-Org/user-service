<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\PasswordResetRateLimitExceededException;

final class PasswordResetRateLimitExceededExceptionTest extends UnitTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new PasswordResetRateLimitExceededException();

        $this->assertSame(
            'Password reset rate limit exceeded. Please try again later.',
            $exception->getMessage()
        );
    }

    public function testExceptionCode(): void
    {
        $exception = new PasswordResetRateLimitExceededException();

        $this->assertSame(0, $exception->getCode());
    }

    public function testTranslationTemplate(): void
    {
        $exception = new PasswordResetRateLimitExceededException();

        $this->assertSame(
            'error.password-reset-rate-limit-exceeded',
            $exception->getTranslationTemplate()
        );
    }
}
