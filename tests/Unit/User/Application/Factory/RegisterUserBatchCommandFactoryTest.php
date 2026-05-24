<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\UserRegisterBatchDto;
use App\User\Application\Factory\RegisterUserBatchCommandFactory;
use InvalidArgumentException;

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

        $command = $factory->create(new UserRegisterBatchDto($users));

        $this->assertInstanceOf(RegisterUserBatchCommand::class, $command);
        $this->assertCount(self::BATCH_SIZE, $command->users);
        $this->assertSame(
            array_column($users, 'email'),
            $command->users->emails()
        );
    }

    public function testCreateRejectsInvalidPayload(): void
    {
        $factory = new RegisterUserBatchCommandFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Batch user payload must contain string email, initials, and password fields.'
        );

        /** @psalm-suppress InvalidArgument */
        $factory->create(new UserRegisterBatchDto([(object) [
            'email' => $this->faker->email(),
            'initials' => $this->faker->word(),
            'password' => $this->faker->password(),
        ],
        ]));
    }
}
