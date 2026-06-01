<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Auth\Support;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Tests\Shared\Auth\Support\ControllableCommandBus;
use App\Tests\Unit\UnitTestCase;

final class ControllableCommandBusTest extends UnitTestCase
{
    public function testDispatchDelegatesToInnerBusWithoutInterceptor(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $response = $this->createMock(CommandResponseInterface::class);
        $inner = $this->createMock(CommandBusInterface::class);
        $inner->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($response);

        $commandBus = new ControllableCommandBus($inner);

        $this->assertSame($response, $commandBus->dispatch($command));
    }

    public function testDispatchUsesInterceptorWhenConfigured(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $response = $this->createMock(CommandResponseInterface::class);
        $inner = $this->createMock(CommandBusInterface::class);
        $inner->expects($this->never())->method('dispatch');

        $commandBus = new ControllableCommandBus($inner);
        $commandBus->interceptWith(
            static function (
                CommandInterface $receivedCommand
            ) use ($command, $response): CommandResponseInterface {
                self::assertSame($command, $receivedCommand);

                return $response;
            }
        );

        $this->assertSame($response, $commandBus->dispatch($command));
    }

    public function testResetRestoresInnerBusDelegation(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $response = $this->createMock(CommandResponseInterface::class);
        $inner = $this->createMock(CommandBusInterface::class);
        $inner->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($response);

        $commandBus = new ControllableCommandBus($inner);
        $commandBus->interceptWith(
            static fn (CommandInterface $_command): null => null
        );
        $commandBus->reset();

        $this->assertSame($response, $commandBus->dispatch($command));
    }
}
