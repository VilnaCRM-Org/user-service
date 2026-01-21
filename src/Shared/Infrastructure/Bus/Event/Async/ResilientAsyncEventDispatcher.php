<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Application\Bus\Event\AsyncEventDispatcherInterface;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Factory\SqsDispatchFailureMetricFactoryInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Resilient async event dispatcher using Symfony Messenger + SQS
 *
 * Layer 1 Resilience: If SQS dispatch fails, log + emit metric, never throw.
 * This ensures the main request always succeeds regardless of queue availability.
 */
final readonly class ResilientAsyncEventDispatcher implements AsyncEventDispatcherInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private SqsDispatchFailureMetricFactoryInterface $metricFactory
    ) {
    }

    public function dispatch(DomainEvent ...$events): bool
    {
        $allSucceeded = true;

        foreach ($events as $event) {
            if (!$this->dispatchSingle($event)) {
                $allSucceeded = false;
            }
        }

        return $allSucceeded;
    }

    private function dispatchSingle(DomainEvent $event): bool
    {
        try {
            $envelope = DomainEventEnvelope::fromEvent($event);
            $this->messageBus->dispatch($envelope);

            $this->logger->debug('Domain event dispatched to async queue', [
                'event_id' => $event->eventId(),
                'event_type' => $event::class,
                'event_name' => $event::eventName(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            $this->handleDispatchFailure($event, $exception);

            return false;
        }
    }

    private function handleDispatchFailure(DomainEvent $event, \Throwable $exception): void
    {
        // Note: Do NOT log payload - it may contain PII (GDPR/SOC2 compliance)
        $this->logger->error('Failed to dispatch domain event to async queue', [
            'event_id' => $event->eventId(),
            'event_type' => $event::class,
            'event_name' => $event::eventName(),
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ]);

        try {
            $metric = $this->metricFactory->create($event::class);
            $this->metricsEmitter->emit($metric);
        } catch (\Throwable $metricException) {
            $this->logger->warning('Failed to emit SQS dispatch failure metric', [
                'error' => $metricException->getMessage(),
            ]);
        }
    }
}
