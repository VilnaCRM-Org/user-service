<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 */
final class MultiEventTestSubscriber implements DomainEventSubscriberInterface
{
    private bool $event1Called = false;
    private bool $event2Called = false;

    public function __invoke(TestEvent|TestCommand $message): void
    {
        if ($message instanceof TestEvent) {
            $this->event1Called = true;
        } else {
            $this->event2Called = true;
        }
    }

    public function isEvent1Called(): bool
    {
        return $this->event1Called;
    }

    public function isEvent2Called(): bool
    {
        return $this->event2Called;
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{TestEvent::class, TestCommand::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestEvent::class, TestCommand::class];
    }
}
