<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPatchDto;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Processor\UserPatchProcessor;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

final class UserPatchProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;
    private CommandBusInterface $commandBus;
    private UpdateUserCommandFactoryInterface $mockUpdateUserCommandFactory;
    private GetUserQueryHandler $getUserQueryHandler;
    private UserPatchProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOperation =
            $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
        $this->getUserQueryHandler = $this->createMock(
            GetUserQueryHandler::class
        );
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->mockUpdateUserCommandFactory = $this->createMock(
            UpdateUserCommandFactoryInterface::class
        );
        $this->processor = new UserPatchProcessor(
            $this->commandBus,
            $this->mockUpdateUserCommandFactory,
            $this->getUserQueryHandler
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

        $this->testProcessSetExpectations($user, $updateData, $userId);

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

        $this->testProcessWithoutFullParamsSetExpectations(
            $user,
            $updateData,
            $userId
        );

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

        $this->testProcessWithSpacesPassedSetExpectations(
            $user,
            $updateData,
            $userId
        );

        $result = $this->processor->process(
            new UserPatchDto($newEmail, $newInitials, $password, $newPassword),
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessUserNotFound(): void
    {
        $userId = $this->faker->uuid();

        $this->getUserQueryHandler->expects(
            $this->once()
        )
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());

        $newEmail = $this->faker->email();
        $newInitials = $this->faker->name();
        $newPassword = $this->faker->password();
        $oldPassword = $this->faker->password();

        $this->expectException(UserNotFoundException::class);

        $this->processor->process(
            new UserPatchDto(
                $newEmail,
                $newInitials,
                $oldPassword,
                $newPassword
            ),
            $this->mockOperation,
            ['id' => $userId]
        );
    }

    private function testProcessSetExpectations(
        UserInterface $user,
        UserUpdate $updateData,
        string $userId
    ): void {
        $command = $this->createCommand($user, $updateData);
        $this->expectUserQueryHandler($userId, $user);
        $this->expectUpdateUserCommandFactory($user, $updateData, $command);
        $this->expectCommandBusDispatch($command);
    }

    private function testProcessWithoutFullParamsSetExpectations(
        UserInterface $user,
        UserUpdate $updateData,
        string $userId
    ): void {
        $command = $this->createCommand($user, $updateData);
        $this->expectUserQueryHandler($userId, $user);
        $this->expectUpdateUserCommandFactory($user, $updateData, $command);
        $this->expectCommandBusDispatch($command);
    }

    private function testProcessWithSpacesPassedSetExpectations(
        UserInterface $user,
        UserUpdate $updateData,
        string $userId
    ): void {
        $command = $this->createCommand($user, $updateData);
        $this->expectUserQueryHandler($userId, $user);
        $this->expectUpdateUserCommandFactory($user, $updateData, $command);
        $this->expectCommandBusDispatch($command);
    }

    private function createCommand(
        UserInterface $user,
        UserUpdate $updateData
    ): object {
        return $this->updateUserCommandFactory->create($user, $updateData);
    }

    private function expectUserQueryHandler(
        string $userId,
        UserInterface $user
    ): void {
        $this->getUserQueryHandler->expects(
            $this->once()
        )
            ->method('handle')
            ->with($userId)
            ->willReturn($user);
    }

    private function expectUpdateUserCommandFactory(
        UserInterface $user,
        UserUpdate $updateData,
        object $command
    ): void {
        $this->mockUpdateUserCommandFactory->expects(
            $this->once()
        )
            ->method('create')
            ->with($user, $updateData)
            ->willReturn($command);
    }

    private function expectCommandBusDispatch(object $command): void
    {
        $this->commandBus->expects(
            $this->once()
        )
            ->method('dispatch')
            ->with($command);
    }
}
