<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Command;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;

class InMemorySymfonyCommandBus implements CommandBusInterface
{
    private MessageBus $bus;

    /**
     * @param iterable<CommandHandlerInterface> $commandHandlers
     */
    public function __construct(
        MessageBusFactory $busFactory,
        iterable $commandHandlers
    ) {
        $this->bus = $busFactory->create($commandHandlers);
    }

    /**
     * @throws \Throwable
     */
    #[\Override]
    public function dispatch(CommandInterface $command): void
    {
        $this->dispatchCommand($command);
    }

    /**
     * @throws \Throwable
     */
    private function dispatchCommand(CommandInterface $command): void
    {
        try {
            $this->bus->dispatch($command);
        } catch (
            NoHandlerForMessageException|HandlerFailedException $error
        ) {
            $this->handleDispatchException($error, $command);
        }
    }

    /**
     * @throws \Throwable
     */
    private function handleDispatchException(
        \Throwable $error,
        CommandInterface $command
    ): never {
        if ($error instanceof NoHandlerForMessageException) {
            throw $this->commandNotRegistered($command);
        }

        if ($error instanceof HandlerFailedException) {
            throw $this->unwrapHandlerFailure($error);
        }

        throw $error;
    }

    private function commandNotRegistered(
        CommandInterface $command
    ): CommandNotRegisteredException {
        return new CommandNotRegisteredException($command);
    }

    private function unwrapHandlerFailure(
        HandlerFailedException $error
    ): \Throwable {
        return $error->getPrevious() ?? $error;
    }
}
