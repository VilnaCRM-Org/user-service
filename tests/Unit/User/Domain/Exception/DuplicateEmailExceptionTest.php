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
        $email = $this->faker->safeEmail();
        $exception = new DuplicateEmailException($email);

        $this->assertSame(
            sprintf('Email "%s" is already registered', $email),
            $exception->getMessage()
        );
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $previous = new RuntimeException($this->faker->sentence());
        $exception = new DuplicateEmailException($this->faker->safeEmail(), $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testGetTranslationTemplate(): void
    {
        $exception = new DuplicateEmailException($this->faker->safeEmail());

        $this->assertSame('email.not.unique', $exception->getTranslationTemplate());
    }

    public function testGetTranslationArgs(): void
    {
        $exception = new DuplicateEmailException($this->faker->safeEmail());

        $this->assertSame([], $exception->getTranslationArgs());
    }

    public function testExtendsDomainException(): void
    {
        $this->assertTrue(
            (new DuplicateEmailException($this->faker->safeEmail())) instanceof DomainException
        );
    }
}
