<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class PasswordChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $email,
        string $eventId,
        string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($body['email'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'password.changed';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}
