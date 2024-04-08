<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPatchDto;
use App\User\Application\DTO\UserPutDto;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Processor\UserPatchProcessor;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

final class UserPatchProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;
    private UserRepositoryInterface $userRepository;
    private CommandBusInterface $commandBus;
    private UpdateUserCommandFactoryInterface $mockUpdateUserCommandFactory;
    private UserPatchProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOperation =
            $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->mockUpdateUserCommandFactory = $this->createMock(
            UpdateUserCommandFactoryInterface::class
        );
        $this->processor = new UserPatchProcessor(
            $this->userRepository,
            $this->commandBus,
            $this->mockUpdateUserCommandFactory
        );
    }

    public function testProcess(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();
        $newPassword = $this->faker->password();
        $newInitials = $this->faker->name();
        $newEmail = $this->faker->email();
        $uuid = $this->uuidTransformer->transformFromString($userId);

        $updateData =
            new UserUpdate($newEmail, $newInitials, $newPassword, $password);

        $user = $this->userFactory->create($email, $initials, $password, $uuid);

        $this->testProcessSetExpectations($user, $updateData);

        $result = $this->processor->process(
            new UserPatchDto($newEmail, $newInitials, $password, $newPassword),
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessWithoutFullParams(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();
        $newPassword = '';
        $newInitials = '';
        $newEmail = '';
        $uuid = $this->uuidTransformer->transformFromString($userId);

        $user = $this->userFactory->create($email, $initials, $password, $uuid);
        $updateData = new UserUpdate($email, $initials, $password, $password);

        $this->testProcessWithoutFullParamsSetExpectations($user, $updateData);

        $result = $this->processor->process(
            new UserPatchDto($newEmail, $newInitials, $password, $newPassword),
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessWithSpacesPassed(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();
        $newPassword = ' ';
        $newInitials = ' ';
        $newEmail = ' ';
        $uuid = $this->uuidTransformer->transformFromString($userId);

        $user = $this->userFactory->create($email, $initials, $password, $uuid);
        $updateData = new UserUpdate($email, $initials, $password, $password);

        $this->testProcessWithSpacesPassedSetExpectations($user, $updateData);

        $result = $this->processor->process(
            new UserPatchDto($newEmail, $newInitials, $password, $newPassword),
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $newEmail = $this->faker->email();
        $newInitials = $this->faker->name();
        $newPassword = $this->faker->password();
        $oldPassword = $this->faker->password();
        $userPutDto =
            new UserPutDto($newEmail, $newInitials, $oldPassword, $newPassword);

        $this->expectException(UserNotFoundException::class);

        $this->processor->process(
            $userPutDto,
            $this->mockOperation,
            ['id' => $this->faker->uuid()]
        );
    }

    private function testProcessSetExpectations(
        UserInterface $user,
        UserUpdate $updateData
    ): void {
        $command = $this->updateUserCommandFactory->create($user, $updateData);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function testProcessWithoutFullParamsSetExpectations(
        UserInterface $user,
        UserUpdate $updateData
    ): void {
        $command = $this->updateUserCommandFactory->create(
            $user,
            $updateData
        );

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function testProcessWithSpacesPassedSetExpectations(
        UserInterface $user,
        UserUpdate $updateData
    ): void {
        $command = $this->updateUserCommandFactory->create(
            $user,
            $updateData
        );

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}
