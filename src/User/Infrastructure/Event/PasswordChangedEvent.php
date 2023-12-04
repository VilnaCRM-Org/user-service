<?php

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

class PasswordChangedEvent extends DomainEvent
{
    public function __construct(
        string $userId,
        private string $email,
        string $eventId = null,
        string $occurredOn = null
    ) {
        parent::__construct($userId, $eventId, $occurredOn);
    }

    public static function fromPrimitives(string $aggregateId, array $body, string $eventId, string $occurredOn): DomainEvent
    {
        return new self($aggregateId, $body['email'], $eventId, $occurredOn);
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

    public function getEmail(): string
    {
        return $this->email;
    }
}
