<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Support;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final readonly class RegisterUserCommandExpectationHelper
{
    public function __construct(
        private SignUpCommandFactoryInterface&MockObject $signUpCommandFactory,
        private CommandBusInterface&MockObject $commandBus,
    ) {
    }

    public function expectRegistration(
        string $email,
        string $initials,
        string $password,
        RegisterUserCommand $command,
    ): void {
        $this->signUpCommandFactory->expects(TestCase::once())
            ->method('create')
            ->with($email, $initials, $password)
            ->willReturn($command);

        $this->commandBus->expects(TestCase::once())
            ->method('dispatch')
            ->with($command)
            ->willReturn(null);
    }
}
