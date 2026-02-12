<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestEvent extends DomainEvent
{
    /**
     * @param array<string, string|object> $body
     */
    #[\Override]
    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): self
    {
        return new self($eventId, $occurredOn);
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
}
