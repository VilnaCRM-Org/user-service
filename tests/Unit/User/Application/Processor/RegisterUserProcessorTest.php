<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Tests\Unit\User\Application\Support\RegisterUserCommandTestCase;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Processor\RegisterUserProcessor;
use App\User\Domain\Exception\UserNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

final class RegisterUserProcessorTest extends RegisterUserCommandTestCase
{
    private Operation&MockObject $mockOperation;
    private RegisterUserProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegisterUserCommandContext();
        $this->mockOperation = $this->createMock(Operation::class);
        $this->processor = $this->createProcessor();
    }

    public function testProcessReturnsExistingUserWithoutDispatch(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userRegisterDto = new UserRegisterDto($email, $initials, $password);
        $existingUser =
            $this->createUser($email, $initials, $password);

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

        $userRegisterDto = new UserRegisterDto($email, $initials, $password);

        $signUpCommand =
            $this->signUpCommandFactory->create($email, $initials, $password);
        $user = $this->createUser($email, $initials, $password);

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

    private function createProcessor(): RegisterUserProcessor
    {
        return new RegisterUserProcessor(
            $this->commandBus,
            $this->mockSignUpCommandFactory,
            $this->findUserByEmailQueryHandler
        );
    }
}
