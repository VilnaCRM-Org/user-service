<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\Factory\RegisterUserBatchCommandFactory;

final class RegisterUserBatchCommandFactoryTest extends UnitTestCase
{
    private const BATCH_SIZE = 2;

    public function testCreate(): void
    {
        $users = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $users[] = [
                'email' => $this->faker->email(),
                'initials' => $this->faker->name(),
                'password' => $this->faker->password(),
            ];
        }
        $factory = new RegisterUserBatchCommandFactory();

        $command = $factory->create($users);

        $this->assertInstanceOf(RegisterUserBatchCommand::class, $command);
        $this->assertSame($users, $command->users);
    }
}
