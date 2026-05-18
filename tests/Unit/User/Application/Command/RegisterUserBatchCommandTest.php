<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;

final class RegisterUserBatchCommandTest extends UnitTestCase
{
    private const BATCH_SIZE = 2;

    public function testConstructor(): void
    {
        $users = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $users[] = [
                'email' => $this->faker->email(),
                'initials' => $this->faker->name(),
                'password' => $this->faker->password(),
            ];
        }

        $command = new RegisterUserBatchCommand($users);

        $this->assertSame($users, $command->users);
    }
}
