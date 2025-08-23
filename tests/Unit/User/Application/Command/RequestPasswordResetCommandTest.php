<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;

final class RequestPasswordResetCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $email = $this->faker->email();
        
        $command = new RequestPasswordResetCommand($email);
        
        $this->assertEquals($email, $command->email);
    }

    public function testImplementsCommandInterface(): void
    {
        $email = $this->faker->email();
        
        $command = new RequestPasswordResetCommand($email);
        
        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testIsReadonly(): void
    {
        $email = $this->faker->email();
        
        $command = new RequestPasswordResetCommand($email);
        
        // Verify we can access the property
        $this->assertEquals($email, $command->email);
        
        // The readonly modifier is enforced by PHP at compile time,
        // so we just verify the property is accessible
        $reflection = new \ReflectionClass($command);
        $this->assertTrue($reflection->getProperty('email')->isReadOnly());
    }

    public function testWithEmptyEmail(): void
    {
        $command = new RequestPasswordResetCommand('');
        
        $this->assertEquals('', $command->email);
    }

    public function testWithValidEmail(): void
    {
        $email = 'test@example.com';
        
        $command = new RequestPasswordResetCommand($email);
        
        $this->assertEquals($email, $command->email);
    }
}