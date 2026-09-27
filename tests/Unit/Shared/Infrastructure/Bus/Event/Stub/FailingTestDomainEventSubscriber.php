<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final class FailingTestDomainEventSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(TestDomainEvent $_event): void
    {
        throw new TestSubscriberFailureException('Subscriber failed');
    }

    /**
     * @return array<class-string<TestDomainEvent>>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestDomainEvent::class];
    }
}
