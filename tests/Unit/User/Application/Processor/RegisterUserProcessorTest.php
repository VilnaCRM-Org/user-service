<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Processor\RegisterUserProcessor;
use App\User\Domain\Entity\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class RegisterUserProcessorTest extends UnitTestCase
{
    private Operation&MockObject $mockOperation;
    private SignUpCommandFactoryInterface&MockObject $commandFactory;
    private CommandBusInterface&MockObject $commandBus;
    private RegisterUserProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOperation = $this->createMock(Operation::class);
        $this->commandFactory =
            $this->createMock(SignUpCommandFactoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->processor = new RegisterUserProcessor(
            $this->commandFactory,
            $this->commandBus,
            new CommandResponseTypeGuard()
        );
    }

    public function testProcessDelegatesRegistration(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userRegisterDto = new UserRegisterDto($email, $initials, $password);
        $command = new RegisterUserCommand($email, $initials, $password);
        $user = $this->createMock(UserInterface::class);

        $this->commandFactory->expects($this->once())
            ->method('create')
            ->with($email, $initials, $password)
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn(new RegisterUserCommandResponse($user));

        $returnedUser =
            $this->processor->process($userRegisterDto, $this->mockOperation);

        $this->assertSame($user, $returnedUser);
    }
}
