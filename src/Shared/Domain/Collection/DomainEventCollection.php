<?php

declare(strict_types=1);

namespace App\Shared\Domain\Collection;

use App\Shared\Domain\Bus\Event\DomainEvent;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, DomainEvent>
 */
final class DomainEventCollection implements
    IteratorAggregate,
    Countable
{
    /** @var list<DomainEvent> */
    private array $events;

    public function __construct(DomainEvent ...$events)
    {
        $this->events = array_values($events);
    }

    public function add(DomainEvent $event): self
    {
        $clone = clone $this;
        $clone->events[] = $event;

        return $clone;
    }

    public function merge(self $other): self
    {
        $clone = clone $this;
        $clone->events = array_merge($clone->events, $other->events);

        return $clone;
    }

    public function isEmpty(): bool
    {
        return $this->events === [];
    }

    /**
     * @return list<DomainEvent>
     *
     * @psalm-api
     */
    public function toArray(): array
    {
        return $this->events;
    }

    /**
     * @psalm-return ArrayIterator<int, DomainEvent>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }

    /**
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return count($this->events);
    }
}
