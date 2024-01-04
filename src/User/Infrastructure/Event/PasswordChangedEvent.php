<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

class PasswordChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $email,
        string $eventId = null,
        string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): DomainEvent
    {
        return new self($body['email'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'password.changed';
    }

    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}
