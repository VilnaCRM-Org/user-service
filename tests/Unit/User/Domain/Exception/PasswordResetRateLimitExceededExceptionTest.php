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
        
        $this->assertSame('Password reset rate limit exceeded', $exception->getMessage());
    }
    
    public function testExceptionCode(): void
    {
        $exception = new PasswordResetRateLimitExceededException();
        
        $this->assertSame(0, $exception->getCode());
    }
    
    public function testExceptionWithCustomMessage(): void
    {
        $customMessage = 'Too many password reset attempts';
        $exception = new PasswordResetRateLimitExceededException($customMessage);
        
        $this->assertSame($customMessage, $exception->getMessage());
    }
}