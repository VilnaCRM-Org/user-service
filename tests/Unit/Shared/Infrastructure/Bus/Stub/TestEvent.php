<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestEvent extends DomainEvent
{
    public function __construct(string $eventId, ?string $occurredOn = null)
    {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string|object> $body
     */
    #[\Override]
    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): self
    {
        return new self($eventId, $occurredOn);
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'test.event';
    }

    /**
     * @return array<string, string|object>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [];
    }
}
