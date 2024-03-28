<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommandResponse;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Processor\RegisterUserProcessor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

class RegisterUserProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private SignUpCommandFactoryInterface $signUpCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->mockOperation = $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
    }

    public function testProcess(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $mockSignUpCommandFactory = $this->createMock(SignUpCommandFactoryInterface::class);

        $processor = new RegisterUserProcessor(
            $commandBus,
            $mockSignUpCommandFactory
        );

        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $userRegisterDto = new UserRegisterDto($email, $initials, $password);

        $signUpCommand = $this->signUpCommandFactory->create($email, $initials, $password);
        $createdUser = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $signUpCommand->setResponse(new RegisterUserCommandResponse($createdUser));

        $mockSignUpCommandFactory->expects($this->once())
            ->method('create')
            ->with(
                $userRegisterDto->email,
                $userRegisterDto->initials,
                $userRegisterDto->password
            )
            ->willReturn($signUpCommand);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($signUpCommand);

        $returnedUser = $processor->process($userRegisterDto, $this->mockOperation);

        $this->assertInstanceOf(User::class, $returnedUser);
        $this->assertEquals($email, $returnedUser->getEmail());
        $this->assertEquals($initials, $returnedUser->getInitials());
        $this->assertEquals($password, $returnedUser->getPassword());
    }
}
