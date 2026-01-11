<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Infrastructure\Bus\Command\CommandNotRegisteredException;
use App\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\UnitTestCase;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;

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

    public function testDispatchSucceeds(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);

        $commandBus = new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );

        $commandBus->dispatch($command);
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

    public function testDispatchUnwrapsHandlerFailedPreviousException(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $messageBus = $this->createMock(MessageBus::class);
        $previous = new \LogicException('original failure');
        $handlerFailed = new HandlerFailedException(
            new Envelope(new \stdClass()),
            [$previous]
        );

        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException($handlerFailed);
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $commandBus = new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );

        $this->expectExceptionObject($previous);

        $commandBus->dispatch($command);
    }

    public function testCommandBusHelperMethodsAreCovered(): void
    {
        $commandBus = $this->createCommandBusForHelperTests();
        $reflection = new ReflectionClass($commandBus);
        $command = $this->createMock(CommandInterface::class);

        $this->testCommandNotRegisteredMethod($commandBus, $reflection, $command);
        $this->testUnwrapHandlerFailureMethod($commandBus, $reflection);
        $this->testHandleDispatchExceptionMethod($commandBus, $reflection, $command);
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

    private function createCommandBusForHelperTests(): InMemorySymfonyCommandBus
    {
        $messageBus = $this->createMock(MessageBus::class);
        $this->messageBusFactory->method('create')->willReturn($messageBus);

        return new InMemorySymfonyCommandBus(
            $this->messageBusFactory,
            $this->commandHandlers
        );
    }

    private function testCommandNotRegisteredMethod(
        InMemorySymfonyCommandBus $commandBus,
        ReflectionClass $reflection,
        CommandInterface $command
    ): void {
        $commandNotRegistered = $reflection->getMethod('commandNotRegistered');
        $this->makeAccessible($commandNotRegistered);

        $exception = $commandNotRegistered->invoke($commandBus, $command);
        self::assertInstanceOf(CommandNotRegisteredException::class, $exception);
    }

    private function testUnwrapHandlerFailureMethod(
        InMemorySymfonyCommandBus $commandBus,
        ReflectionClass $reflection
    ): void {
        $unwrap = $reflection->getMethod('unwrapHandlerFailure');
        $this->makeAccessible($unwrap);
        $handlerFailure = new HandlerFailedException(
            new Envelope(new \stdClass()),
            [new \RuntimeException('failure')]
        );

        $result = $unwrap->invoke($commandBus, $handlerFailure);
        self::assertInstanceOf(\RuntimeException::class, $result);
    }

    private function testHandleDispatchExceptionMethod(
        InMemorySymfonyCommandBus $commandBus,
        ReflectionClass $reflection,
        CommandInterface $command
    ): void {
        $handle = $reflection->getMethod('handleDispatchException');
        $this->makeAccessible($handle);
        $otherError = new \RuntimeException('other failure');

        try {
            $handle->invoke($commandBus, $otherError, $command);
            $this->fail('Expected exception not thrown.');
        } catch (\RuntimeException $caught) {
            self::assertSame($otherError, $caught);
        }
    }
}
