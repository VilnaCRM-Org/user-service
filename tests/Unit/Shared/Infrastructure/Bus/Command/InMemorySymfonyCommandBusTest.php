<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Shared\Infrastructure\Bus\Command\CommandNotRegisteredException;
use App\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class InMemorySymfonyCommandBusTest extends UnitTestCase
{
    private MessageBusFactory $messageBusFactory;

    /**
     * @var array<CommandInterface>
     */
    private array $commandHandlers;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBusFactory =
            $this->createMock(MessageBusFactory::class);
        $this->commandHandlers =
            [$this->createMock(CommandInterface::class)];
    }

    public function testDispatchWithNoHandlerForMessageException(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new NoHandlerForMessageException());
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $commandBus = new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );

        $this->expectException(CommandNotRegisteredException::class);

        $commandBus->dispatch($command);
    }

    public function testDispatchWithHandlerFailedException(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(
                $this->createMock(HandlerFailedException::class)
            );
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $commandBus = new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );

        $this->expectException(HandlerFailedException::class);

        $commandBus->dispatch($command);
    }

    public function testDispatchWithThrowable(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException());
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $commandBus = new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );

        $this->expectException(\RuntimeException::class);

        $commandBus->dispatch($command);
    }

    public function testDispatchReturnsHandledCommandResponse(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $response = $this->createMock(CommandResponseInterface::class);
        $commandBus = $this->createCommandBusReturning($command, $response);

        $this->assertSame($response, $commandBus->dispatch($command));
    }

    public function testDispatchRejectsMultipleHandledResults(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $firstResponse = $this->createMock(CommandResponseInterface::class);
        $secondResponse = $this->createMock(CommandResponseInterface::class);
        $commandBus = $this->createCommandBusReturningMultipleResults(
            $command,
            $firstResponse,
            $secondResponse
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Command %s resolved to 2 handlers; exactly one is required.',
            $command::class
        ));

        $commandBus->dispatch($command);
    }

    public function testDispatchReturnsNullWhenHandlerHasNoResponse(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn(new Envelope($command));
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $commandBus = new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );

        $this->assertNull($commandBus->dispatch($command));
    }

    public function testDispatchReturnsNullWhenHandlerReturnsNull(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $commandBus = $this->createCommandBusReturning($command, null);

        $this->assertNull($commandBus->dispatch($command));
    }

    public function testDispatchRejectsUnsupportedHandlerResult(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $commandBus = $this->createCommandBusReturning($command, 'unexpected-result');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Command handler for %s returned unsupported result string.',
            $command::class
        ));

        $commandBus->dispatch($command);
    }

    private function createCommandBusReturningMultipleResults(
        CommandInterface $command,
        CommandResponseInterface $firstResponse,
        CommandResponseInterface $secondResponse
    ): InMemorySymfonyCommandBus {
        return $this->createCommandBusDispatchingEnvelope(
            $command,
            (new Envelope($command))
                ->with(new HandledStamp($firstResponse, 'first-handler'))
                ->with(new HandledStamp($secondResponse, 'second-handler'))
        );
    }

    private function createCommandBusReturning(
        CommandInterface $command,
        CommandResponseInterface|string|null $result
    ): InMemorySymfonyCommandBus {
        return $this->createCommandBusDispatchingEnvelope(
            $command,
            (new Envelope($command))->with(
                new HandledStamp($result, 'handler')
            )
        );
    }

    private function createCommandBusDispatchingEnvelope(
        CommandInterface $command,
        Envelope $envelope
    ): InMemorySymfonyCommandBus {
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($envelope);
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);

        return new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );
    }
}
