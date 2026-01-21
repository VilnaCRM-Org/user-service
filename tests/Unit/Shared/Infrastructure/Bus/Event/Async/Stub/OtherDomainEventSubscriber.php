<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * Subscriber that only handles OtherDomainEvent (not TestDomainEvent)
 */
final class OtherDomainEventSubscriber implements DomainEventSubscriberInterface
{
    /** @var array<DomainEvent> */
    private array $handled = [];

    public function __invoke(DomainEvent $event): void
    {
        $this->handled[] = $event;
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [OtherDomainEvent::class];
    }

    /**
     * @return array<DomainEvent>
     */
    public function handled(): array
    {
        return $this->handled;
    }
}
