<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Factory\EventSubscriberFailureMetricFactoryInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;

class InMemorySymfonyEventBus implements EventBusInterface
{
    private readonly MessageBusInterface $bus;

    /**
     * @param iterable<DomainEventSubscriberInterface> $subscribers
     */
    public function __construct(
        MessageBusFactory $busFactory,
        iterable $subscribers,
        LoggerInterface $logger,
        BusinessMetricsEmitterInterface $metricsEmitter,
        EventSubscriberFailureMetricFactoryInterface $metricFactory
    ) {
        $this->bus = $busFactory->create(
            $this->decorateSubscribers(
                $subscribers,
                $logger,
                $metricsEmitter,
                $metricFactory
            )
        );
    }

    #[\Override]
    public function publish(DomainEvent ...$events): void
    {
        array_walk($events, [$this, 'dispatchEvent']);
    }

    private function dispatchEvent(DomainEvent $event): void
    {
        try {
            $this->bus->dispatch($event);
        } catch (NoHandlerForMessageException) {
            throw new EventNotRegisteredException($event);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious() ?? $exception;
        }
    }

    /**
     * @param iterable<DomainEventSubscriberInterface> $subscribers
     *
     * @return list<DefensiveEventSubscriberDecorator>
     */
    private function decorateSubscribers(
        iterable $subscribers,
        LoggerInterface $logger,
        BusinessMetricsEmitterInterface $metricsEmitter,
        EventSubscriberFailureMetricFactoryInterface $metricFactory
    ): array {
        $decoratedSubscribers = [];

        foreach ($subscribers as $subscriber) {
            $decoratedSubscribers[] = new DefensiveEventSubscriberDecorator(
                $subscriber,
                $logger,
                $metricsEmitter,
                $metricFactory
            );
        }

        return $decoratedSubscribers;
    }
}
