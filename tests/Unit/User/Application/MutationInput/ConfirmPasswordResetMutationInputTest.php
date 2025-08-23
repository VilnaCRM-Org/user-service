<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\ConfirmPasswordResetMutationInput;

final class ConfirmPasswordResetMutationInputTest extends UnitTestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        
        $input = new ConfirmPasswordResetMutationInput($token, $newPassword);
        
        $this->assertEquals($token, $input->token);
        $this->assertEquals($newPassword, $input->newPassword);
    }

    public function testConstructorWithoutParameters(): void
    {
        $input = new ConfirmPasswordResetMutationInput();
        
        $this->assertNull($input->token);
        $this->assertNull($input->newPassword);
    }

    public function testConstructorWithTokenOnly(): void
    {
        $token = $this->faker->sha256();
        
        $input = new ConfirmPasswordResetMutationInput($token);
        
        $this->assertEquals($token, $input->token);
        $this->assertNull($input->newPassword);
    }

    public function testConstructorWithPasswordOnly(): void
    {
        $newPassword = $this->faker->password();
        
        $input = new ConfirmPasswordResetMutationInput(null, $newPassword);
        
        $this->assertNull($input->token);
        $this->assertEquals($newPassword, $input->newPassword);
    }

    public function testConstructorWithNullParameters(): void
    {
        $input = new ConfirmPasswordResetMutationInput(null, null);
        
        $this->assertNull($input->token);
        $this->assertNull($input->newPassword);
    }

    public function testIsReadonly(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        
        $input = new ConfirmPasswordResetMutationInput($token, $newPassword);
        
        // Verify we can access the properties
        $this->assertEquals($token, $input->token);
        $this->assertEquals($newPassword, $input->newPassword);
        
        // The readonly modifier is enforced by PHP at compile time,
        // so we just verify the properties are accessible
        $reflection = new \ReflectionClass($input);
        $this->assertTrue($reflection->getProperty('token')->isReadOnly());
        $this->assertTrue($reflection->getProperty('newPassword')->isReadOnly());
    }

    public function testWithEmptyStrings(): void
    {
        $input = new ConfirmPasswordResetMutationInput('', '');
        
        $this->assertEquals('', $input->token);
        $this->assertEquals('', $input->newPassword);
    }

    public function testWithComplexPassword(): void
    {
        $token = $this->faker->sha256();
        $complexPassword = 'Test123!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $input = new ConfirmPasswordResetMutationInput($token, $complexPassword);
        
        $this->assertEquals($token, $input->token);
        $this->assertEquals($complexPassword, $input->newPassword);
    }

    public function testWithLongToken(): void
    {
        $longToken = str_repeat('a1b2c3d4', 32); // 256 character token
        $password = $this->faker->password();
        
        $input = new ConfirmPasswordResetMutationInput($longToken, $password);
        
        $this->assertEquals($longToken, $input->token);
        $this->assertEquals($password, $input->newPassword);
    }
}