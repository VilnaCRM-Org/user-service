<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class BaseHealthCheckSubscriber implements EventSubscriberInterface
{
    /**
     * @return string[]
     *
     * @psalm-return array{'App\\Internal\\HealthCheck\\Domain\\Event\\HealthCheckEvent'::class: 'onHealthCheck'}
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [HealthCheckEvent::class => 'onHealthCheck'];
    }

    abstract public function onHealthCheck(HealthCheckEvent $event): void;
}
