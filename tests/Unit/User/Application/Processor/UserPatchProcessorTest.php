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

        $this->faker->seed(1234);

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
        $testData = $this->setupUserForPatchTest();
        $this->setupProcessExpectations(
            $testData->user,
            new UserUpdate(
                $testData->email,
                $testData->initials,
                $testData->password,
                $testData->password
            ),
            $testData->userId
        );
        $this->assertInstanceOf(
            User::class,
            $this->processor->process(
                new UserPatchDto('', '', $testData->password, ''),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    public function testProcessWithSpacesPassed(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupProcessExpectations(
            $testData->user,
            new UserUpdate(
                $testData->email,
                $testData->initials,
                $testData->password,
                $testData->password
            ),
            $testData->userId
        );
        $this->assertInstanceOf(
            User::class,
            $this->processor->process(
                new UserPatchDto(' ', ' ', $testData->password, ' '),
                $this->mockOperation,
                ['id' => $testData->userId]
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

    public function testProcessWithInvalidEmailPreservesOriginal(): void
    {
        $testData = $this->setupUserForPatchTest();
        $result = $this->processWithInvalidInput(
            $testData->user,
            $testData->email,
            $testData->initials,
            $testData->password,
            $testData->userId
        );
        $this->assertEquals(
            $testData->email,
            $result->getEmail()
        );
    }

    private function processWithInvalidInput(
        UserInterface $user,
        string $email,
        string $initials,
        string $password,
        string $userId,
        ?string $invalidEmail = null,
        ?string $invalidInitials = null,
        ?string $invalidPassword = null
    ): UserInterface {
        $invalidEmail = $invalidEmail ?? 'not-an-email';
        $updateData = new UserUpdate(
            $invalidEmail,
            $invalidInitials ?? $initials,
            $invalidPassword ?? $password,
            $password
        );
        $this->setupProcessExpectations($user, $updateData, $userId);
        return $this->processor->process(
            new UserPatchDto(
                $invalidEmail,
                $invalidInitials ?? $initials,
                $password,
                $invalidPassword ?? $password
            ),
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

    private function setupUserForPatchTest(): UserPatchTestData
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

        return new UserPatchTestData(
            $user,
            $email,
            $initials,
            $password,
            $userId
        );
    }
}
