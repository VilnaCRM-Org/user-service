<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * @psalm-suppress UnusedProperty
 */
final class MultiEventTestSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private bool &$event1Called,
        private bool &$event2Called
    ) {
    }

    public function __invoke(TestEvent|TestCommand $message): void
    {
        if ($message instanceof TestEvent) {
            $this->event1Called = true;
        } else {
            $this->event2Called = true;
        }
    }

    /**
     * @return string[]
     *
     * @psalm-return list{TestEvent::class, TestCommand::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestEvent::class, TestCommand::class];
    }
}
