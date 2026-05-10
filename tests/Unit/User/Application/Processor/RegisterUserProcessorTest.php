<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Application\Support\RegisterUserCommandExpectationHelper;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Processor\RegisterUserProcessor;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class RegisterUserProcessorTest extends UnitTestCase
{
    private Operation&MockObject $mockOperation;
    private SignUpCommandFactoryInterface $signUpCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private CommandBusInterface&MockObject $commandBus;
    private SignUpCommandFactoryInterface&MockObject $mockSignUpCommandFactory;
    private FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler;
    private RegisterUserCommandExpectationHelper $commandExpectationHelper;
    private RegisterUserProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->mockOperation =
            $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->mockSignUpCommandFactory = $this->createMock(
            SignUpCommandFactoryInterface::class
        );
        $this->findUserByEmailQueryHandler = $this->createMock(
            FindUserByEmailQueryHandlerInterface::class
        );
        $this->commandExpectationHelper =
            new RegisterUserCommandExpectationHelper(
                $this->findUserByEmailQueryHandler,
                $this->mockSignUpCommandFactory,
                $this->commandBus
            );
        $this->processor = new RegisterUserProcessor(
            $this->commandBus,
            $this->mockSignUpCommandFactory,
            $this->findUserByEmailQueryHandler
        );
    }

    public function testProcessReturnsExistingUserWithoutDispatch(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $uuid =
            $this->uuidTransformer->transformFromString($this->faker->uuid());
        $userRegisterDto = new UserRegisterDto($email, $initials, $password);
        $existingUser =
            $this->userFactory->create($email, $initials, $password, $uuid);

        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn($existingUser);
        $this->mockSignUpCommandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $returnedUser =
            $this->processor->process($userRegisterDto, $this->mockOperation);

        $this->assertSame($existingUser, $returnedUser);
    }

    public function testProcessDispatchesRegistrationAndReturnsCreatedUser(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $uuid =
            $this->uuidTransformer->transformFromString($this->faker->uuid());

        $userRegisterDto = new UserRegisterDto($email, $initials, $password);

        $signUpCommand =
            $this->signUpCommandFactory->create($email, $initials, $password);
        $user = $this->userFactory->create($email, $initials, $password, $uuid);

        $this->commandExpectationHelper->expectRegistration(
            $userRegisterDto->email,
            $userRegisterDto->initials,
            $userRegisterDto->password,
            $signUpCommand,
            $user
        );

        $returnedUser =
            $this->processor->process($userRegisterDto, $this->mockOperation);

        $this->assertSame($user, $returnedUser);
    }

    public function testProcessThrowsWhenCreatedUserCannotBeLoaded(): void
    {
        $email = $this->faker->email();
        $userRegisterDto = new UserRegisterDto(
            $email,
            $this->faker->name(),
            $this->faker->password()
        );
        $signUpCommand = $this->signUpCommandFactory->create(
            $userRegisterDto->email,
            $userRegisterDto->initials,
            $userRegisterDto->password
        );

        $this->expectException(UserNotFoundException::class);
        $this->commandExpectationHelper->expectMissingCreatedUser(
            $email,
            $signUpCommand
        );

        $this->processor->process($userRegisterDto, $this->mockOperation);
    }
}
