<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\UsersUpdatedMetricFactoryInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;

/**
 * Emits business metrics when a user is updated
 *
 * This subscriber listens to EmailChangedEvent and PasswordChangedEvent
 * and emits the UsersUpdated metric for CloudWatch dashboards.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers, wrapped with Layer 2 resilience.
 * DomainEventMessageHandler catches all failures, logs them, and emits metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class UserUpdatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private UsersUpdatedMetricFactoryInterface $metricFactory
    ) {
    }

    /**
     * @psalm-suppress UnusedParam Event parameter required by interface but not used
     */
    public function __invoke(EmailChangedEvent|PasswordChangedEvent $event): void
    {
        $this->metricsEmitter->emit($this->metricFactory->create());
    }

    /**
     * @return string[]
     *
     * @psalm-return list{EmailChangedEvent::class, PasswordChangedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [EmailChangedEvent::class, PasswordChangedEvent::class];
    }
}
