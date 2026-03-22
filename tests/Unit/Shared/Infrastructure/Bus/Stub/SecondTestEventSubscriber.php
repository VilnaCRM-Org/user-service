<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress UnusedProperty
 */
final class SecondTestEventSubscriber implements DomainEventSubscriberInterface
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

    /** @return array<class-string> */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestEvent::class];
    }
}
