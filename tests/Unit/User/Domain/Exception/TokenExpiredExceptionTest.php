<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\TokenExpiredException;

final class TokenExpiredExceptionTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $exception = new TokenExpiredException();
        
        $this->assertEquals('Token has expired', $exception->getMessage());
    }

    public function testExtendsDomainException(): void
    {
        $exception = new TokenExpiredException();
        
        $this->assertInstanceOf(DomainException::class, $exception);
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new TokenExpiredException();
        
        $this->assertEquals('error.token-expired', $exception->getTranslationTemplate());
    }

    public function testExceptionChaining(): void
    {
        $previousException = new \Exception('Previous exception');
        $exception = new TokenExpiredException();
        
        // Test that it can be used in exception chaining context
        $chainedException = new \Exception('Chain test', 0, $exception);
        
        $this->assertSame($exception, $chainedException->getPrevious());
    }

    public function testInstanceCreation(): void
    {
        $exception1 = new TokenExpiredException();
        $exception2 = new TokenExpiredException();
        
        // Each instance should be separate
        $this->assertNotSame($exception1, $exception2);
        
        // But they should have the same message
        $this->assertEquals($exception1->getMessage(), $exception2->getMessage());
        $this->assertEquals($exception1->getTranslationTemplate(), $exception2->getTranslationTemplate());
    }

    public function testDefaultErrorCode(): void
    {
        $exception = new TokenExpiredException();
        
        // Default exception code should be 0
        $this->assertEquals(0, $exception->getCode());
    }
}