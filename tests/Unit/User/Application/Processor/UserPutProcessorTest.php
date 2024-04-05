<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPutDto;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Processor\UserPutProcessor;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

final class UserPutProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOperation =
            $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
    }

    public function testProcess(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $mockUpdateUserCommandFactory =
            $this->createMock(UpdateUserCommandFactoryInterface::class);

        $processor = new UserPutProcessor(
            $userRepository,
            $commandBus,
            $mockUpdateUserCommandFactory
        );

        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userId)
        );
        $updateData = new UserUpdate($email, $initials, $password, $password);
        $command = $this->updateUserCommandFactory->create(
            $user,
            $updateData
        );

        $userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $userPutDto = new UserPutDto($email, $initials, $password, $password);

        $mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData)
            ->willReturn($command);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result = $processor->process(
            $userPutDto,
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessUserNotFound(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $updateUserCommandFactory = $this->createMock(
            UpdateUserCommandFactoryInterface::class
        );

        $processor = new UserPutProcessor(
            $userRepository,
            $commandBus,
            $updateUserCommandFactory
        );

        $userRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $userPutDto = new UserPutDto($email, $initials, $password, $password);

        $this->expectException(UserNotFoundException::class);

        $processor->process(
            $userPutDto,
            $this->mockOperation,
            ['id' => $userId]
        );
    }
}
