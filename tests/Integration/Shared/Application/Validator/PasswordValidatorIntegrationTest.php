<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Application\Validator;

use App\Shared\Application\Validator\Password;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PasswordValidatorIntegrationTest extends IntegrationTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = $this->container->get(ValidatorInterface::class);
    }

    public function testValidPassword(): void
    {
        $violations = $this->validator->validate('Password123', [new Password()]);

        $this->assertCount(0, $violations);
    }

    public function testPasswordTooShort(): void
    {
        $violations = $this->validator->validate('Pass1', [new Password()]);

        $this->assertCount(1, $violations);
        $this->assertEquals('Password must be between 8 and 64 characters long', $violations[0]->getMessage());
    }

    public function testPasswordTooLong(): void
    {
        $longPassword = str_repeat('A1', 33); // 66 characters
        $violations = $this->validator->validate($longPassword, [new Password()]);

        $this->assertCount(1, $violations);
        $this->assertEquals('Password must be between 8 and 64 characters long', $violations[0]->getMessage());
    }

    public function testPasswordExactlyMinLength(): void
    {
        $violations = $this->validator->validate('Passwo1!', [new Password()]);

        $this->assertCount(0, $violations);
    }

    public function testPasswordExactlyMaxLength(): void
    {
        $maxLengthPassword = str_repeat('A1', 32); // 64 characters
        $violations = $this->validator->validate($maxLengthPassword, [new Password()]);

        $this->assertCount(0, $violations);
    }

    public function testPasswordOneCharacterBelowMaxLength(): void
    {
        $nearMaxPassword = str_repeat('A1', 31) . 'A'; // 63 characters
        $violations = $this->validator->validate($nearMaxPassword, [new Password()]);

        $this->assertCount(0, $violations);
    }

    public function testPasswordOneCharacterAboveMaxLength(): void
    {
        $aboveMaxPassword = str_repeat('A1', 32) . 'X'; // 65 characters
        $violations = $this->validator->validate($aboveMaxPassword, [new Password()]);

        $this->assertCount(1, $violations);
        $this->assertEquals('Password must be between 8 and 64 characters long', $violations[0]->getMessage());
    }

    public function testPasswordWithoutNumber(): void
    {
        $violations = $this->validator->validate('Password', [new Password()]);

        $this->assertCount(1, $violations);
        $this->assertEquals('Password must contain at least one number', $violations[0]->getMessage());
    }

    public function testPasswordWithoutUppercase(): void
    {
        $violations = $this->validator->validate('password123', [new Password()]);

        $this->assertCount(1, $violations);
        $this->assertEquals('Password must contain at least one uppercase letter', $violations[0]->getMessage());
    }
}
