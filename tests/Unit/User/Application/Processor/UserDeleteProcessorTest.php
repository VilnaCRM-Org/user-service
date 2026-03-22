<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DeleteUserCommand;
use App\User\Application\Factory\DeleteUserCommandFactoryInterface;
use App\User\Application\Processor\UserDeleteProcessor;
use App\User\Domain\Entity\User;

final class UserDeleteProcessorTest extends UnitTestCase
{
    public function testProcessDispatchesDeleteCommand(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandFactory = $this->createMock(DeleteUserCommandFactoryInterface::class);
        $operation = $this->createMock(Operation::class);
        $user = $this->createMock(User::class);
        $command = $this->createMock(DeleteUserCommand::class);

        $commandFactory->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn($command);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $processor = new UserDeleteProcessor($commandBus, $commandFactory);

        $result = $processor->process($user, $operation);

        $this->assertSame($user, $result);
    }
}
