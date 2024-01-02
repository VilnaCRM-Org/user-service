<?php

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\ConfirmationToken;

class UserConfirmedEvent extends DomainEvent
{
    public function __construct(
        public readonly ConfirmationToken $token,
        string $eventId = null,
        string $occurredOn = null)
    {
        parent::__construct($eventId, $occurredOn);
    }

    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): DomainEvent
    {
        return new self($body['token'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.confirmed';
    }

    public function toPrimitives(): array
    {
        return ['token' => $this->token];
    }
}
