<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 */
final class FirstTestEventSubscriber implements DomainEventSubscriberInterface
{
    private bool $called = false;

    public function __invoke(TestEvent $_event): void
    {
        $this->called = true;
    }

    public function isCalled(): bool
    {
        return $this->called;
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
