<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Command\RegisterUserCommandResponse;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Processor\RegisterUserProcessor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class RegisterUserProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private SignUpCommandFactoryInterface $signUpCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private CommandBusInterface $commandBus;
    private SignUpCommandFactoryInterface $mockSignUpCommandFactory;
    private RegisterUserProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->mockOperation =
            $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->mockSignUpCommandFactory = $this->createMock(
            SignUpCommandFactoryInterface::class
        );
        $this->processor = new RegisterUserProcessor(
            $this->commandBus,
            $this->mockSignUpCommandFactory
        );
    }

    public function testProcess(): void
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
        $signUpCommand->setResponse(
            new RegisterUserCommandResponse($user)
        );

        $this->setExpectations($userRegisterDto, $signUpCommand);

        $returnedUser =
            $this->processor->process($userRegisterDto, $this->mockOperation);

        $this->assertInstanceOf(User::class, $returnedUser);
        $this->assertEquals($email, $returnedUser->getEmail());
        $this->assertEquals($initials, $returnedUser->getInitials());
        $this->assertEquals($password, $returnedUser->getPassword());
    }

    private function setExpectations(
        UserRegisterDto $userRegisterDto,
        RegisterUserCommand $signUpCommand,
    ): void {
        $this->mockSignUpCommandFactory->expects($this->once())
            ->method('create')
            ->with(
                $userRegisterDto->email,
                $userRegisterDto->initials,
                $userRegisterDto->password
            )
            ->willReturn($signUpCommand);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($signUpCommand);
    }
}
