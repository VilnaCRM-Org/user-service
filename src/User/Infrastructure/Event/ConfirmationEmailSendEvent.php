<?php

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\ConfirmationToken;

class ConfirmationEmailSendEvent extends DomainEvent
{
    public function __construct(
        public readonly ConfirmationToken $token,
        public readonly string $emailAddress,
        string $eventId = null,
        string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): DomainEvent
    {
        return new self($body['token'], $body['emailAddress'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'confirmation_email.send';
    }

    public function toPrimitives(): array
    {
        return [
            'emailAddress' => $this->emailAddress,
            'token' => $this->token,
        ];
    }
}
