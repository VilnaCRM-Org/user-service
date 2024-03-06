<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\DuplicateEmailException;

class DuplicateEmailExceptionTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testMessageContainsEmail(): void
    {
        $email = $this->faker->email();
        $exception = new DuplicateEmailException($email);

        $this->assertStringContainsString($email, $exception->getMessage());
    }

    public function testMessageContainsErrorMessage(): void
    {
        $email = $this->faker->email();
        $exception = new DuplicateEmailException($email);

        $expectedMessage = "$email address is already registered";
        $this->assertStringContainsString($expectedMessage, $exception->getMessage());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue((new DuplicateEmailException($this->faker->email())) instanceof DomainException);
    }
}
