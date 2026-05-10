<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Command;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use LogicException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Stamp\HandledStamp;

readonly class InMemorySymfonyCommandBus implements CommandBusInterface
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
    public function dispatch(CommandInterface $command): ?CommandResponseInterface
    {
        try {
            $envelope = $this->bus->dispatch($command);
        } catch (NoHandlerForMessageException) {
            throw new CommandNotRegisteredException($command);
        } catch (HandlerFailedException $error) {
            throw $error->getPrevious() ?? $error;
        }

        return $this->extractCommandResponse($command, $envelope);
    }

    private function extractCommandResponse(
        CommandInterface $command,
        Envelope $envelope
    ): ?CommandResponseInterface {
        $handledStamp = $this->singleHandledStamp($command, $envelope);
        $result = $handledStamp?->getResult();

        if ($result === null) {
            return null;
        }

        if ($result instanceof CommandResponseInterface) {
            return $result;
        }

        throw new LogicException(sprintf(
            'Command handler for %s returned unsupported result %s.',
            $command::class,
            get_debug_type($result)
        ));
    }

    private function singleHandledStamp(
        CommandInterface $command,
        Envelope $envelope
    ): ?HandledStamp {
        $handledStamps = $envelope->all(HandledStamp::class);
        if (count($handledStamps) > 1) {
            throw new LogicException(sprintf(
                'Command %s resolved to %d handlers; exactly one is required.',
                $command::class,
                count($handledStamps)
            ));
        }

        return $handledStamps[0] ?? null;
    }
}
