<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\Factory\SignUpCommandFactory;

class SignUpCommandFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new SignUpCommandFactory();
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $command = $factory->create($email, $initials, $password);

        $this->assertInstanceOf(SignUpCommand::class, $command);
    }
}
