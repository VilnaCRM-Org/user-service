<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Domain\Entity\PasswordResetToken;

final class ConfirmPasswordResetCommandTest extends UnitTestCase
{
    private PasswordResetToken $token;
    private string $newPassword;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = new PasswordResetToken(
            $this->faker->sha256(),
            $this->faker->uuid()
        );
        $this->newPassword = $this->faker->password();
    }

    public function testConstructor(): void
    {
        $command = new ConfirmPasswordResetCommand($this->token, $this->newPassword);
        
        $this->assertEquals($this->token, $command->token);
        $this->assertEquals($this->newPassword, $command->newPassword);
    }

    public function testImplementsCommandInterface(): void
    {
        $command = new ConfirmPasswordResetCommand($this->token, $this->newPassword);
        
        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testIsReadonly(): void
    {
        $command = new ConfirmPasswordResetCommand($this->token, $this->newPassword);
        
        // Verify we can access the properties
        $this->assertEquals($this->token, $command->token);
        $this->assertEquals($this->newPassword, $command->newPassword);
        
        // The readonly modifier is enforced by PHP at compile time,
        // so we just verify the properties are accessible
        $reflection = new \ReflectionClass($command);
        $this->assertTrue($reflection->getProperty('token')->isReadOnly());
        $this->assertTrue($reflection->getProperty('newPassword')->isReadOnly());
    }

    public function testWithComplexPassword(): void
    {
        $complexPassword = 'Test123!@#$%^&*()';
        
        $command = new ConfirmPasswordResetCommand($this->token, $complexPassword);
        
        $this->assertEquals($complexPassword, $command->newPassword);
    }

    public function testWithMinimalPassword(): void
    {
        $minimalPassword = 'a';
        
        $command = new ConfirmPasswordResetCommand($this->token, $minimalPassword);
        
        $this->assertEquals($minimalPassword, $command->newPassword);
    }

    public function testTokenPreservation(): void
    {
        $command = new ConfirmPasswordResetCommand($this->token, $this->newPassword);
        
        // Verify that the exact same token instance is preserved
        $this->assertSame($this->token, $command->token);
        $this->assertEquals($this->token->getTokenValue(), $command->token->getTokenValue());
        $this->assertEquals($this->token->getUserID(), $command->token->getUserID());
    }
}