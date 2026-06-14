<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\UserRegisterBatchDto;
use App\User\Application\Factory\RegisterUserBatchCommandFactory;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @param non-empty-string $mutation
     */
    public function testCreateRejectsInvalidPayload(string $mutation): void
    {
        $factory = new RegisterUserBatchCommandFactory();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Batch user payload must contain string email, initials, and password fields.'
        );

        $factory->create(
            $this->createBatchDtoWithPayload(
                $this->createInvalidPayload($mutation)
            )
        );
    }

    /**
     * @return array<string, array{non-empty-string}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'non-array row' => ['non-array row'],
            'missing email' => ['missing email'],
            'missing initials' => ['missing initials'],
            'missing password' => ['missing password'],
            'non-string email' => ['non-string email'],
            'non-string initials' => ['non-string initials'],
            'non-string password' => ['non-string password'],
        ];
    }

    /**
     * @param non-empty-string $mutation
     *
     * @return object|array<string, int|string>
     */
    private function createInvalidPayload(string $mutation): object|array
    {
        $payload = $this->createValidPayload();

        if ($mutation === 'non-array row') {
            return (object) $payload;
        }

        if (str_starts_with($mutation, 'missing ')) {
            unset($payload[substr($mutation, 8)]);

            return $payload;
        }

        $payload[substr($mutation, 11)] = $this->faker->randomNumber();

        return $payload;
    }

    /**
     * @return array{email: string, initials: string, password: string}
     */
    private function createValidPayload(): array
    {
        return [
            'email' => $this->faker->email(),
            'initials' => $this->faker->name(),
            'password' => $this->faker->password(),
        ];
    }

    private function createBatchDtoWithPayload(object|array $payload): UserRegisterBatchDto
    {
        $reflection = new ReflectionClass(UserRegisterBatchDto::class);
        $dto = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('users')->setValue($dto, [$payload]);

        return $dto;
    }
}
