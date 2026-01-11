<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class InMemorySymfonyEventBus implements EventBusInterface
{
    private MessageBusInterface $bus;

    /**
     * @param iterable<DomainEventSubscriberInterface> $subscribers
     */
    public function __construct(
        MessageBusFactory $busFactory,
        iterable $subscribers
    ) {
        $this->bus = $this->initializeBus($busFactory, $subscribers);
    }

    #[\Override]
    public function publish(DomainEvent ...$events): void
    {
        array_walk(
            $events,
            [$this, 'dispatchEvent']
        );
    }

    /**
     * @param iterable<DomainEventSubscriberInterface> $subscribers
     */
    private function initializeBus(
        MessageBusFactory $busFactory,
        iterable $subscribers
    ): MessageBus {
        return $busFactory->create($subscribers);
    }

    private function dispatchEvent(DomainEvent $event): void
    {
        try {
            $this->bus->dispatch($event);
        } catch (
            NoHandlerForMessageException|HandlerFailedException $exception
        ) {
            $this->handleDispatchException($exception, $event);
        }
    }

    private function handleDispatchException(
        \Throwable $exception,
        DomainEvent $event
    ): never {
        if ($exception instanceof NoHandlerForMessageException) {
            throw $this->eventNotRegistered($event);
        }

        if ($exception instanceof HandlerFailedException) {
            throw $this->unwrapHandlerFailure($exception);
        }

        throw $exception;
    }

    private function eventNotRegistered(
        DomainEvent $event
    ): EventNotRegisteredException {
        return new EventNotRegisteredException($event);
    }

    private function unwrapHandlerFailure(
        HandlerFailedException $exception
    ): \Throwable {
        return $exception->getPrevious() ?? $exception;
    }
}
