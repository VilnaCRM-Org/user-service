<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RequestPasswordResetDto;

final class RequestPasswordResetDtoTest extends UnitTestCase
{
    public function testConstructorWithEmail(): void
    {
        $email = $this->faker->email();
        
        $dto = new RequestPasswordResetDto($email);
        
        $this->assertEquals($email, $dto->email);
    }

    public function testConstructorWithoutEmail(): void
    {
        $dto = new RequestPasswordResetDto();
        
        $this->assertNull($dto->email);
    }

    public function testConstructorWithNullEmail(): void
    {
        $dto = new RequestPasswordResetDto(null);
        
        $this->assertNull($dto->email);
    }

    public function testIsReadonly(): void
    {
        $email = $this->faker->email();
        
        $dto = new RequestPasswordResetDto($email);
        
        // Verify we can access the property
        $this->assertEquals($email, $dto->email);
        
        // The readonly modifier is enforced by PHP at compile time,
        // so we just verify the property is accessible
        $reflection = new \ReflectionClass($dto);
        $this->assertTrue($reflection->getProperty('email')->isReadOnly());
    }

    public function testWithEmptyString(): void
    {
        $dto = new RequestPasswordResetDto('');
        
        $this->assertEquals('', $dto->email);
    }

    public function testWithValidEmailFormats(): void
    {
        $emails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'first.last+tag@example.org',
            'simple@test.io'
        ];

        foreach ($emails as $email) {
            $dto = new RequestPasswordResetDto($email);
            $this->assertEquals($email, $dto->email);
        }
    }
}