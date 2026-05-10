<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Factory\EventSubscriberFailureMetricFactoryInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

final readonly class DefensiveEventSubscriberDecorator implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private DomainEventSubscriberInterface $inner,
        private LoggerInterface $logger,
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private EventSubscriberFailureMetricFactoryInterface $metricFactory
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        try {
            ($this->inner)($event);
        } catch (\Throwable $exception) {
            $this->handleSubscriberFailure($event, $exception);
        }
    }

    /**
     * @return array<class-string<DomainEvent>>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return $this->inner->subscribedTo();
    }

    private function handleSubscriberFailure(
        DomainEvent $event,
        \Throwable $exception
    ): void {
        $this->logger->error(
            'Domain event subscriber execution failed',
            $this->failureContext($event, $exception)
        );

        try {
            $this->emitFailureMetric($event);
        } catch (\Throwable $metricException) {
            $this->logger->warning(
                'Failed to emit subscriber failure metric',
                $this->failureContext($event, $metricException)
            );
        }
    }

    private function emitFailureMetric(DomainEvent $event): void
    {
        $metric = $this->metricFactory->create(
            $this->inner::class,
            $event::class
        );

        $this->metricsEmitter->emit($metric);
    }

    /**
     * @return array<string, string>
     */
    private function failureContext(
        DomainEvent $event,
        \Throwable $exception
    ): array {
        return [
            'subscriber' => $this->inner::class,
            'event_id' => $event->eventId(),
            'event_type' => $event::class,
            'event_name' => $event::eventName(),
            'exception_class' => $exception::class,
            'exception_code' => (string) $exception->getCode(),
        ];
    }
}
