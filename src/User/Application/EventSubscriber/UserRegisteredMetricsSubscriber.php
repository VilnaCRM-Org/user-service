<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\UsersRegisteredMetricFactoryInterface;
use App\User\Domain\Event\UserRegisteredEvent;

final readonly class UserRegisteredMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private UsersRegisteredMetricFactoryInterface $metricFactory
    ) {
    }

    /**
     * @psalm-suppress UnusedParam Event parameter required by interface but not used
     */
    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->metricsEmitter->emit($this->metricFactory->create());
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }
}
