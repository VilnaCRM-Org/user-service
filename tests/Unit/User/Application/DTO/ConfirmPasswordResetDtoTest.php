<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\ConfirmPasswordResetDto;

final class ConfirmPasswordResetDtoTest extends UnitTestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        
        $dto = new ConfirmPasswordResetDto($token, $newPassword);
        
        $this->assertEquals($token, $dto->token);
        $this->assertEquals($newPassword, $dto->newPassword);
    }

    public function testConstructorWithoutParameters(): void
    {
        $dto = new ConfirmPasswordResetDto();
        
        $this->assertNull($dto->token);
        $this->assertNull($dto->newPassword);
    }

    public function testConstructorWithTokenOnly(): void
    {
        $token = $this->faker->sha256();
        
        $dto = new ConfirmPasswordResetDto($token);
        
        $this->assertEquals($token, $dto->token);
        $this->assertNull($dto->newPassword);
    }

    public function testConstructorWithPasswordOnly(): void
    {
        $newPassword = $this->faker->password();
        
        $dto = new ConfirmPasswordResetDto(null, $newPassword);
        
        $this->assertNull($dto->token);
        $this->assertEquals($newPassword, $dto->newPassword);
    }

    public function testConstructorWithNullParameters(): void
    {
        $dto = new ConfirmPasswordResetDto(null, null);
        
        $this->assertNull($dto->token);
        $this->assertNull($dto->newPassword);
    }

    public function testIsReadonly(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        
        $dto = new ConfirmPasswordResetDto($token, $newPassword);
        
        // Verify we can access the properties
        $this->assertEquals($token, $dto->token);
        $this->assertEquals($newPassword, $dto->newPassword);
        
        // The readonly modifier is enforced by PHP at compile time,
        // so we just verify the properties are accessible
        $reflection = new \ReflectionClass($dto);
        $this->assertTrue($reflection->getProperty('token')->isReadOnly());
        $this->assertTrue($reflection->getProperty('newPassword')->isReadOnly());
    }

    public function testWithEmptyStrings(): void
    {
        $dto = new ConfirmPasswordResetDto('', '');
        
        $this->assertEquals('', $dto->token);
        $this->assertEquals('', $dto->newPassword);
    }

    public function testWithComplexPassword(): void
    {
        $token = $this->faker->sha256();
        $complexPassword = 'Test123!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $dto = new ConfirmPasswordResetDto($token, $complexPassword);
        
        $this->assertEquals($complexPassword, $dto->newPassword);
    }
}