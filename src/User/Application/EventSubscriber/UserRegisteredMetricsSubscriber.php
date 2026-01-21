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

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->metricsEmitter->emit($this->metricFactory->create());
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }
}
