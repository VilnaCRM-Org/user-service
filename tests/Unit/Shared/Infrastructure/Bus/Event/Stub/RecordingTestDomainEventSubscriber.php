<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final class RecordingTestDomainEventSubscriber implements DomainEventSubscriberInterface
{
    private ?TestDomainEvent $handledEvent = null;

    public function __invoke(TestDomainEvent $event): void
    {
        $this->handledEvent = $event;
    }

    /**
     * @return array<class-string<TestDomainEvent>>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestDomainEvent::class];
    }

    public function handledEvent(): ?TestDomainEvent
    {
        return $this->handledEvent;
    }

    public function wasCalled(): bool
    {
        return $this->handledEvent !== null;
    }
}
