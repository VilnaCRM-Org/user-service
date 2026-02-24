<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use PHPUnit\Framework\Assert;

final class RecordingEventBus implements EventBusInterface
{
    /** @var list<DomainEvent> */
    private array $published = [];

    #[\Override]
    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;
        }
    }

    /**
     * @template T of DomainEvent
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    public function lastPublished(string $eventClass): DomainEvent
    {
        Assert::assertNotEmpty($this->published, 'No events were published.');
        $event = end($this->published);
        Assert::assertInstanceOf($eventClass, $event);

        return $event;
    }
}
