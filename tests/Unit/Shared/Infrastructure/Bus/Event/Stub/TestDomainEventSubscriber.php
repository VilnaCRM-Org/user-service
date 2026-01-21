<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final readonly class TestDomainEventSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(private ?\Closure $handler = null)
    {
    }

    public function __invoke(DomainEvent $event): void
    {
        if ($this->handler !== null) {
            ($this->handler)($event);
        }
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [TestDomainEvent::class];
    }
}
