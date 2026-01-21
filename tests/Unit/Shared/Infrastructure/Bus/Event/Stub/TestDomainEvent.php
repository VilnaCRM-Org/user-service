<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $value,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct(
            $eventId ?? $this->generateEventId(),
            $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'test.domain_event';
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
        ];
    }

    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        return new self(
            id: $body['id'],
            value: $body['value'],
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    private function generateEventId(): string
    {
        return uniqid('test_domain_event_', true);
    }
}
