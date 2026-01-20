<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final class TestDomainEventSubscriber implements DomainEventSubscriberInterface
{
    /**
     * @var array<class-string<DomainEvent>>
     */
    private array $subscriptions;
    private int $callCount = 0;
    private ?DomainEvent $lastEvent = null;
    private ?\Throwable $exception;

    /**
     * @param array<class-string<DomainEvent>> $subscriptions
     */
    public function __construct(array $subscriptions, ?\Throwable $exception = null)
    {
        $this->subscriptions = $subscriptions;
        $this->exception = $exception;
    }

    public function __invoke(DomainEvent $event): void
    {
        $this->callCount++;
        $this->lastEvent = $event;

        if ($this->exception !== null) {
            throw $this->exception;
        }
    }

    /**
     * @return array<class-string<DomainEvent>>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return $this->subscriptions;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function getLastEvent(): ?DomainEvent
    {
        return $this->lastEvent;
    }
}
