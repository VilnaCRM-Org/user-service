<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class UserConfirmedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $tokenValue,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
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
        return new self($body['tokenValue'], $eventId, $occurredOn);
    }

    /**
     * @return string
     *
     * @psalm-return 'user.confirmed'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.confirmed';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{tokenValue: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return ['tokenValue' => $this->tokenValue];
    }
}
