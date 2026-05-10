<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;

final class RegisterUserCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $command = new RegisterUserCommand($email, $initials, $password);

        $this->assertInstanceOf(RegisterUserCommand::class, $command);
        $this->assertSame($email, $command->email);
        $this->assertSame($initials, $command->initials);
        $this->assertSame($password, $command->password);
    }
}
