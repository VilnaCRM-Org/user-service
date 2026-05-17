<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\DuplicateEmailException;
use RuntimeException;

final class DuplicateEmailExceptionTest extends UnitTestCase
{
    public function testMessage(): void
    {
        $exception = new DuplicateEmailException('test@example.com');

        $this->assertSame(
            'Email "test@example.com" is already registered',
            $exception->getMessage()
        );
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $previous = new RuntimeException('Duplicate key');
        $exception = new DuplicateEmailException('test@example.com', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new DuplicateEmailException('test@example.com');

        $this->assertSame('email.unique', $exception->getTranslationTemplate());
    }

    public function testGetTranslationArgs(): void
    {
        $exception = new DuplicateEmailException('test@example.com');

        $this->assertSame([], $exception->getTranslationArgs());
    }

    public function testExtendsDomainException(): void
    {
        $this->assertTrue(
            (new DuplicateEmailException('test@example.com')) instanceof DomainException
        );
    }
}
