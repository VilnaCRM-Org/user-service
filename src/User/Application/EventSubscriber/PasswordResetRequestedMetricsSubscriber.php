<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\PasswordResetRequestsMetricFactoryInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;

/**
 * Emits business metrics when a password reset is requested
 *
 * This subscriber listens to PasswordResetRequestedEvent and emits
 * the PasswordResetRequests metric for CloudWatch dashboards.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers, wrapped with Layer 2 resilience.
 * DomainEventMessageHandler catches all failures, logs them, and emits metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class PasswordResetRequestedMetricsSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private PasswordResetRequestsMetricFactoryInterface $metricFactory
    ) {
    }

    /**
     * @psalm-suppress UnusedParam Event parameter required by interface but not used
     */
    public function __invoke(PasswordResetRequestedEvent $event): void
    {
        $this->metricsEmitter->emit($this->metricFactory->create());
    }

    /**
     * @return string[]
     *
     * @psalm-return list{PasswordResetRequestedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [PasswordResetRequestedEvent::class];
    }
}
