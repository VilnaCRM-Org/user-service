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

        $this->setupProcessExpectations($user, $updateData, $userId);

        $result = $this->processor->process(
            new UserPatchDto($newEmail, $newInitials, $password, $newPassword),
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessWithoutFullParams(): void
    {
        [
            $user,
            $email,
            $initials,
            $password,
            $userId,
        ] = $this->setupUserForPatchTest();

        $this->setupProcessExpectations(
            $user,
            new UserUpdate($email, $initials, $password, $password),
            $userId
        );

        $this->assertInstanceOf(
            User::class,
            $this->processor->process(
                new UserPatchDto('', '', $password, ''),
                $this->mockOperation,
                ['id' => $userId]
            )
        );
    }

    public function testProcessWithSpacesPassed(): void
    {
        [
            $user,
            $email,
            $initials,
            $password,
            $userId,
        ] = $this->setupUserForPatchTest();

        $this->setupProcessExpectations(
            $user,
            new UserUpdate($email, $initials, $password, $password),
            $userId
        );

        $this->assertInstanceOf(
            User::class,
            $this->processor->process(
                new UserPatchDto(' ', ' ', $password, ' '),
                $this->mockOperation,
                ['id' => $userId]
            )
        );
    }

    public function testProcessUserNotFound(): void
    {
        $userId = $this->faker->uuid();

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $this->processor->process(
            new UserPatchDto(
                $this->faker->email(),
                $this->faker->name(),
                $this->faker->password(),
                $this->faker->password()
            ),
            $this->mockOperation,
            ['id' => $userId]
        );
    }

    public function testProcessWithInvalidEmailReturnsDefault(): void
    {
        [
            $user,
            $email,
            $initials,
            $password,
            $userId,
        ] = $this->setupUserForPatchTest();
        $result = $this->processWithInvalidEmail(
            $user,
            $email,
            $initials,
            $password,
            $userId
        );
        $this->assertEquals(
            $email,
            $result->getEmail()
        );
    }

    private function processWithInvalidEmail(
        UserInterface $user,
        string $email,
        string $initials,
        string $password,
        string $userId
    ): UserInterface {
        $invalidEmail = 'not-an-email';
        $updateData = new UserUpdate(
            $invalidEmail,
            $initials,
            $password,
            $password
        );
        $this->setupProcessExpectations($user, $updateData, $userId);
        return $this->processor->process(
            (object) [
                'email' => $invalidEmail,
                'initials' => $initials,
                'newPassword' => $password,
                'oldPassword' => $password,
            ],
            $this->mockOperation,
            ['id' => $userId]
        );
    }

    private function setupProcessExpectations(
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

    /**
     * @return array{0: UserInterface, 1: string, 2: string, 3: string, 4: string}
     */
    private function setupUserForPatchTest(): array
    {
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

        return [
            $user,
            $email,
            $initials,
            $password,
            $userId,
        ];
    }
}
