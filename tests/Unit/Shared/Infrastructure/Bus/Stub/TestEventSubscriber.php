<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/** @psalm-suppress UnusedClass */
final class TestEventSubscriber implements DomainEventSubscriberInterface
{
    /**
     * @psalm-suppress UnusedParam
     */
    public function __invoke(TestEvent $event): void
    {
        // Test subscriber
    }

    /**
     * @return string[]
     *
     * @psalm-return list{TestEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestEvent::class];
    }
}
