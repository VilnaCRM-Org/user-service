<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

abstract class AuthProcessorTestCase extends UnitTestCase
{
    /**
     * @template TCommand of CommandInterface
     *
     * @param class-string<TCommand> $commandClass
     * @param callable(TCommand): void $assertCommand
     */
    protected function expectDispatchMatchingCommand(
        CommandBusInterface&MockObject $commandBus,
        string $commandClass,
        CommandResponseInterface $response,
        callable $assertCommand,
    ): void {
        $commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (CommandInterface $command) use (
                    $commandClass,
                    $assertCommand,
                ): bool {
                    self::assertInstanceOf($commandClass, $command);
                    $assertCommand($command);

                    return true;
                }
            ))
            ->willReturn($response);
    }
}
