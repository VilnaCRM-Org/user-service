<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestOtherDomainEvent extends DomainEvent
{
    #[\Override]
    public static function eventName(): string
    {
        return 'test.other.domain.event';
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [];
    }

    /**
     * @param array<string, string> $body
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
