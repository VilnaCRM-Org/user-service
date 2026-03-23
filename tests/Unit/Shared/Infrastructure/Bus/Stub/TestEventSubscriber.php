<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * @psalm-api
 */
final class TestEventSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(TestEvent $_event): void
    {
        // Test subscriber
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{TestEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestEvent::class];
    }
}
