<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Support;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final readonly class RegisterUserCommandExpectationHelper
{
    public function __construct(
        private FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler,
        private SignUpCommandFactoryInterface&MockObject $signUpCommandFactory,
        private CommandBusInterface&MockObject $commandBus,
    ) {
    }

    public function expectMissingCreatedUser(
        string $email,
        RegisterUserCommand $command,
    ): void {
        $this->findUserByEmailQueryHandler->expects(TestCase::exactly(2))
            ->method('find')
            ->with($email)
            ->willReturn(null);

        $this->signUpCommandFactory->expects(TestCase::once())
            ->method('create')
            ->willReturn($command);

        $this->commandBus->expects(TestCase::once())
            ->method('dispatch')
            ->with($command);
    }

    public function expectRegistration(
        string $email,
        string $initials,
        string $password,
        RegisterUserCommand $command,
        UserInterface $user,
    ): void {
        $this->findUserByEmailQueryHandler->expects(TestCase::exactly(2))
            ->method('find')
            ->with($email)
            ->willReturnOnConsecutiveCalls(null, $user);

        $this->signUpCommandFactory->expects(TestCase::once())
            ->method('create')
            ->with($email, $initials, $password)
            ->willReturn($command);

        $this->commandBus->expects(TestCase::once())
            ->method('dispatch')
            ->with($command);
    }
}
