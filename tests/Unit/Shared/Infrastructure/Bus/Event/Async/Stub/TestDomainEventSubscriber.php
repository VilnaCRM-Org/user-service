<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final class TestDomainEventSubscriber implements DomainEventSubscriberInterface
{
    /** @var array<DomainEvent> */
    private array $handled = [];
    private bool $shouldFail = false;

    public function __invoke(DomainEvent $event): void
    {
        if ($this->shouldFail) {
            throw new \RuntimeException('Subscriber failed');
        }

        $this->handled[] = $event;
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [TestDomainEvent::class];
    }

    public function failOnNextCall(): void
    {
        $this->shouldFail = true;
    }

    /**
     * @return array<DomainEvent>
     */
    public function handled(): array
    {
        return $this->handled;
    }

    public function clear(): void
    {
        $this->handled = [];
        $this->shouldFail = false;
    }
}
