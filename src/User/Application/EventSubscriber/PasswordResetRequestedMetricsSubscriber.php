<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\PasswordResetRequestsMetricFactoryInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;

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
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [PasswordResetRequestedEvent::class];
    }
}
