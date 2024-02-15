<?php

namespace App\Tests\Unit\User\Application\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Exception\DuplicateEmailException;

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

        $expectedMessage = $email . ' address is already registered. Please use a different email address or try logging in.';
        $this->assertStringContainsString($expectedMessage, $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $this->assertTrue((new DuplicateEmailException($this->faker->email())) instanceof \LogicException);
    }
}
