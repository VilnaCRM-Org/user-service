<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\UsersUpdatedMetricFactoryInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;

final readonly class UserUpdatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private UsersUpdatedMetricFactoryInterface $metricFactory
    ) {
    }

    public function __invoke(EmailChangedEvent|PasswordChangedEvent $event): void
    {
        $this->metricsEmitter->emit($this->metricFactory->create());
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [EmailChangedEvent::class, PasswordChangedEvent::class];
    }
}
