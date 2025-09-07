<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Strategy;

use App\Shared\Application\Validator\Strategy\PasswordValidationChecks;
use PHPUnit\Framework\TestCase;

final class PasswordValidationChecksTest extends TestCase
{
    private PasswordValidationChecks $validationChecks;

    protected function setUp(): void
    {
        $this->validationChecks = new PasswordValidationChecks();
    }

    public function testHasValidLengthWithValidPassword(): void
    {
        $result = $this->validationChecks->hasValidLength('ValidPass123');

        $this->assertTrue($result);
    }

    public function testHasValidLengthWithMinimumLength(): void
    {
        $result = $this->validationChecks->hasValidLength('Pass123!');

        $this->assertTrue($result);
    }

    public function testHasValidLengthWithTooShortPassword(): void
    {
        $result = $this->validationChecks->hasValidLength('Pass1');

        $this->assertFalse($result);
    }

    public function testHasValidLengthWithTooLongPassword(): void
    {
        $longPassword = str_repeat('a', 65);
        $result = $this->validationChecks->hasValidLength($longPassword);

        $this->assertFalse($result);
    }

    public function testHasValidLengthWithMaximumLength(): void
    {
        $maxPassword = str_repeat('a', 64);
        $result = $this->validationChecks->hasValidLength($maxPassword);

        $this->assertTrue($result);
    }

    public function testHasNumberWithNumber(): void
    {
        $result = $this->validationChecks->hasNumber('Password123');

        $this->assertTrue($result);
    }

    public function testHasNumberWithoutNumber(): void
    {
        $result = $this->validationChecks->hasNumber('Password');

        $this->assertFalse($result);
    }

    public function testHasNumberWithMultipleNumbers(): void
    {
        $result = $this->validationChecks->hasNumber('Pass123word456');

        $this->assertTrue($result);
    }

    public function testHasUppercaseWithUppercase(): void
    {
        $result = $this->validationChecks->hasUppercase('Password123');

        $this->assertTrue($result);
    }

    public function testHasUppercaseWithoutUppercase(): void
    {
        $result = $this->validationChecks->hasUppercase('password123');

        $this->assertFalse($result);
    }

    public function testHasUppercaseWithMultipleUppercase(): void
    {
        $result = $this->validationChecks->hasUppercase('PASSWORD123');

        $this->assertTrue($result);
    }

    public function testHasValidLengthWithNonStringValue(): void
    {
        $result = $this->validationChecks->hasValidLength(123);

        $this->assertFalse($result);
    }
}
