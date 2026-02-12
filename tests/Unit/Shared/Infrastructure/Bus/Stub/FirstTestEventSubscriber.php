<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress UnusedProperty
 */
final class FirstTestEventSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(private bool &$called)
    {
    }

    /**
     * @psalm-suppress UnusedParam
     */
    public function __invoke(TestEvent $event): void
    {
        $this->called = true;
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
