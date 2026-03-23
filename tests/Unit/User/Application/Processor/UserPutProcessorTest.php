<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPutDto;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Processor\UserPutProcessor;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class UserPutProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;
    private CommandBusInterface $commandBus;
    private UpdateUserCommandFactoryInterface $mockUpdateUserCommandFactory;
    private GetUserQueryHandler $getUserQueryHandler;
    private UserPutProcessor $processor;
    private Security $security;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOperation = $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
        $this->initializeMocks();
        $this->processor = new UserPutProcessor(
            $this->commandBus,
            $this->mockUpdateUserCommandFactory,
            $this->getUserQueryHandler,
            $this->security
        );
    }

    public function testProcess(): void
    {
        [$user, $updateData, $userPutDto, $userId] = $this->prepareUserPutTestData();
        $this->expectUserQueryReturns($userId, $user);
        $this->prepareSessionExpectations($user, $updateData);

        $result = $this->processor->process(
            $userPutDto,
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessUserNotFound(): void
    {
        [$userPutDto, $userId] = $this->prepareUserNotFoundTestData();
        $this->expectUserNotFound($userId);
        $this->expectException(UserNotFoundException::class);

        $this->processor->process(
            $userPutDto,
            $this->mockOperation,
            ['id' => $userId]
        );
    }

    public function testProcessWithNullSecurityToken(): void
    {
        [$user, $updateData, $userPutDto, $userId] = $this->prepareUserPutTestData();
        $this->expectUserQueryReturns($userId, $user);
        $this->security->expects($this->once())->method('getToken')->willReturn(null);
        $this->prepareEmptySessionCommand($user, $updateData);

        $result = $this->processor->process(
            $userPutDto,
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessWithNonStringSessionId(): void
    {
        [$user, $updateData, $userPutDto, $userId] = $this->prepareUserPutTestData();
        $this->expectUserQueryReturns($userId, $user);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')->with('sid')->willReturn(null);
        $this->security->expects($this->once())->method('getToken')->willReturn($token);
        $this->prepareEmptySessionCommand($user, $updateData);

        $result = $this->processor->process(
            $userPutDto,
            $this->mockOperation,
            ['id' => $userId]
        );

        $this->assertInstanceOf(User::class, $result);
    }

    private function initializeMocks(): void
    {
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandler::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->mockUpdateUserCommandFactory = $this->createMock(
            UpdateUserCommandFactoryInterface::class
        );
    }

    /**
     * @return array{UserInterface, UserUpdate, UserPutDto, string}
     */
    private function prepareUserPutTestData(): array
    {
        $userId = $this->faker->uuid();
        [$email, $initials, $password] = $this->generateUserData();
        $user = $this->createUser($email, $initials, $password, $userId);
        $updateData = $this->createUserUpdate($email, $initials, $password);
        $userPutDto = $this->createUserPutDto($email, $initials, $password);

        return [$user, $updateData, $userPutDto, $userId];
    }

    private function createUser(
        string $email,
        string $initials,
        string $password,
        string $userId
    ): UserInterface {
        return $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userId),
        );
    }

    private function createUserUpdate(
        string $email,
        string $initials,
        string $password
    ): UserUpdate {
        return new UserUpdate(
            $email,
            $initials,
            $password,
            $password,
        );
    }

    private function createUserPutDto(
        string $email,
        string $initials,
        string $password
    ): UserPutDto {
        return new UserPutDto(
            $email,
            $initials,
            $password,
            $password,
        );
    }

    /**
     * @return array{string, string, string}
     */
    private function generateUserData(): array
    {
        return [
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
        ];
    }

    /**
     * @return array{UserPutDto, string}
     */
    private function prepareUserNotFoundTestData(): array
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        return [new UserPutDto($email, $initials, $password, $password), $userId];
    }

    private function expectUserNotFound(string $userId): void
    {
        $this->getUserQueryHandler
            ->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());
    }

    private function expectUserQueryReturns(
        string $userId,
        UserInterface $user
    ): void {
        $this->getUserQueryHandler
            ->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);
    }

    private function prepareSessionExpectations(
        UserInterface $user,
        UserUpdate $updateData
    ): void {
        $currentSessionId = $this->faker->uuid();
        $this->prepareSecurityTokenExpectation($currentSessionId);
        $this->prepareCommandDispatch($user, $updateData, $currentSessionId);
    }

    private function prepareSecurityTokenExpectation(
        string $sessionId
    ): void {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getAttribute')
            ->with('sid')
            ->willReturn($sessionId);
        $this->security->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
    }

    private function prepareCommandDispatch(
        UserInterface $user,
        UserUpdate $updateData,
        string $sessionId
    ): void {
        $command = $this->updateUserCommandFactory->create($user, $updateData, $sessionId);
        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData, $sessionId)
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function prepareEmptySessionCommand(
        UserInterface $user,
        UserUpdate $updateData
    ): void {
        $command = $this->updateUserCommandFactory->create($user, $updateData, '');
        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData, '')
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}
