<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\BatchUserRegistrationInput;
use App\User\Application\DTO\BatchUserRegistrationInputCollection;

final class RegisterUserBatchCommandTest extends UnitTestCase
{
    private const BATCH_SIZE = 2;

    public function testConstructor(): void
    {
        $batchUsers = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $batchUsers[] = new BatchUserRegistrationInput(
                $this->faker->email(),
                $this->faker->name(),
                $this->faker->password()
            );
        }
        $users = new BatchUserRegistrationInputCollection(...$batchUsers);

        $command = new RegisterUserBatchCommand($users);

        $this->assertSame($users, $command->users);
        $this->assertSame($batchUsers, iterator_to_array($command->users));
    }
}
