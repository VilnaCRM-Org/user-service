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

    /**
     * @dataProvider invalidPayloadProvider
     *
     * @param object|array<string, int|string> $payload
     */
    public function testCreateRejectsInvalidPayload(object|array $payload): void
    {
        $factory = new RegisterUserBatchCommandFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Batch user payload must contain string email, initials, and password fields.'
        );

        /** @psalm-suppress InvalidArgument */
        $factory->create(new UserRegisterBatchDto([$payload]));
    }

    /**
     * @return array<string, array{object|array<string, int|string>}>
     */
    public static function invalidPayloadProvider(): array
    {
        return array_merge(
            self::nonArrayPayloads(),
            self::missingFieldPayloads(),
            self::invalidScalarPayloads()
        );
    }

    /**
     * @return array<string, array{object|array<string, int|string>}>
     */
    private static function nonArrayPayloads(): array
    {
        return [
            'non-array row' => [(object) [
                'email' => 'user@example.test',
                'initials' => 'User',
                'password' => 'password',
            ],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, int|string>}>
     */
    private static function missingFieldPayloads(): array
    {
        return [
            'missing email' => [[
                'initials' => 'User',
                'password' => 'password',
            ],
            ],
            'missing initials' => [[
                'email' => 'user@example.test',
                'password' => 'password',
            ],
            ],
            'missing password' => [[
                'email' => 'user@example.test',
                'initials' => 'User',
            ],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, int|string>}>
     */
    private static function invalidScalarPayloads(): array
    {
        return array_merge(
            self::invalidEmailPayloads(),
            self::invalidInitialsPayloads(),
            self::invalidPasswordPayloads()
        );
    }

    /**
     * @return array<string, array{array<string, int|string>}>
     */
    private static function invalidEmailPayloads(): array
    {
        return [
            'non-string email' => [[
                'email' => 123,
                'initials' => 'User',
                'password' => 'password',
            ],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, int|string>}>
     */
    private static function invalidInitialsPayloads(): array
    {
        return [
            'non-string initials' => [[
                'email' => 'user@example.test',
                'initials' => 123,
                'password' => 'password',
            ],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, int|string>}>
     */
    private static function invalidPasswordPayloads(): array
    {
        return [
            'non-string password' => [[
                'email' => 'user@example.test',
                'initials' => 'User',
                'password' => 123,
            ],
            ],
        ];
    }
}
