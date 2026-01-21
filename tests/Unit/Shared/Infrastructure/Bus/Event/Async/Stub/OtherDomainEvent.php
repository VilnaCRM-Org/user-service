<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Another test domain event that TestDomainEventSubscriber does NOT subscribe to
 */
final class OtherDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $data,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        if (! isset($body['data']) || ! is_scalar($body['data'])) {
            throw new \InvalidArgumentException(
                'Missing or invalid "data" field in event body'
            );
        }

        return new self(
            data: (string) $body['data'],
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'test.other_domain_event';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return ['data' => $this->data];
    }
}
