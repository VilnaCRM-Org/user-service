<?php

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

class UserRegisteredEvent extends DomainEvent
{
    public function __construct(
        string $id,
        private string $email,
        string $eventId = null,
        string $occurredOn = null
    ) {
        parent::__construct($id, $eventId, $occurredOn);
    }

    public static function fromPrimitives(string $aggregateId, array $body, string $eventId, string $occurredOn): DomainEvent
    {
        return new self($aggregateId, $body['email'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.registered';
    }

    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
        ];
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
