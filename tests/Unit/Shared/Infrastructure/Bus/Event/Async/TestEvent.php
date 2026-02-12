<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestEvent extends DomainEvent
{
    public function __construct(string $eventId, ?string $occurredOn = null)
    {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @return string
     *
     * @psalm-return 'test.event'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'test.event';
    }

    /**
     * @return array
     *
     * @psalm-return array<never, never>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [];
    }

    /**
     * @param array<string, string> $body
     *
     * @return self
     */
    #[\Override]
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($eventId, $occurredOn);
    }
}
