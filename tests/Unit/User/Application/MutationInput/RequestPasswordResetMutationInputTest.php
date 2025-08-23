<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\MutationInput;
use App\User\Application\MutationInput\RequestPasswordResetMutationInput;

final class RequestPasswordResetMutationInputTest extends UnitTestCase
{
    public function testConstructorWithEmail(): void
    {
        $email = $this->faker->email();
        
        $input = new RequestPasswordResetMutationInput($email);
        
        $this->assertEquals($email, $input->email);
    }

    public function testConstructorWithoutEmail(): void
    {
        $input = new RequestPasswordResetMutationInput();
        
        $this->assertNull($input->email);
    }

    public function testConstructorWithNullEmail(): void
    {
        $input = new RequestPasswordResetMutationInput(null);
        
        $this->assertNull($input->email);
    }

    public function testIsReadonly(): void
    {
        $email = $this->faker->email();
        
        $input = new RequestPasswordResetMutationInput($email);
        
        // Verify we can access the property
        $this->assertEquals($email, $input->email);
        
        // The readonly modifier is enforced by PHP at compile time,
        // so we just verify the property is accessible
        $reflection = new \ReflectionClass($input);
        $this->assertTrue($reflection->getProperty('email')->isReadOnly());
    }

    public function testWithEmptyString(): void
    {
        $input = new RequestPasswordResetMutationInput('');
        
        $this->assertEquals('', $input->email);
    }

    public function testWithValidEmailFormats(): void
    {
        $emails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'first.last+tag@example.org',
            'simple@test.io',
            'numbers123@domain456.com'
        ];

        foreach ($emails as $email) {
            $input = new RequestPasswordResetMutationInput($email);
            $this->assertEquals($email, $input->email);
        }
    }

    public function testWithSpecialCharacters(): void
    {
        $email = 'test+special.chars@example-domain.co.uk';
        
        $input = new RequestPasswordResetMutationInput($email);
        
        $this->assertEquals($email, $input->email);
    }
}