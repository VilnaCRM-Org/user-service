<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserRegisterBatchDto;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use TypeError;

final class UserRegisterBatchDtoTest extends UnitTestCase
{
    private const BATCH_SIZE = 2;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testDefault(): void
    {
        $users = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $email = $this->faker->email();
            $initials = $this->faker->name();
            $password = $this->faker->password();

            $users[] = $this->userFactory->create(
                $email,
                $initials,
                $password,
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        $dto = new UserRegisterBatchDto($users);
        $this->assertSame($users, $dto->users);
    }

    public function testEmpty(): void
    {
        $dto = new UserRegisterBatchDto();
        $this->assertSame([], $dto->users);
    }

    public function testNull(): void
    {
        $this->expectException(TypeError::class);

        new UserRegisterBatchDto(null);
    }
}
