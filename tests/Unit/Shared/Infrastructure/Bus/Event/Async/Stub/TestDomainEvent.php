<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $aggregateId,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string|object> $body
     */
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        return new self(
            aggregateId: (string) $body['aggregateId'],
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'test.domain_event';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return ['aggregateId' => $this->aggregateId];
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }
}
