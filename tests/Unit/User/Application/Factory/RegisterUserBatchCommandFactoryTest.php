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
    private const VALID_EMAIL = 'user@example.test';
    private const VALID_INITIALS = 'User';
    private const VALID_PASSWORD = 'password';

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
                'email' => self::VALID_EMAIL,
                'initials' => self::VALID_INITIALS,
                'password' => self::VALID_PASSWORD,
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
                'initials' => self::VALID_INITIALS,
                'password' => self::VALID_PASSWORD,
            ],
            ],
            'missing initials' => [[
                'email' => self::VALID_EMAIL,
                'password' => self::VALID_PASSWORD,
            ],
            ],
            'missing password' => [[
                'email' => self::VALID_EMAIL,
                'initials' => self::VALID_INITIALS,
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
                'initials' => self::VALID_INITIALS,
                'password' => self::VALID_PASSWORD,
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
                'email' => self::VALID_EMAIL,
                'initials' => 123,
                'password' => self::VALID_PASSWORD,
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
                'email' => self::VALID_EMAIL,
                'initials' => self::VALID_INITIALS,
                'password' => 123,
            ],
            ],
        ];
    }
}
