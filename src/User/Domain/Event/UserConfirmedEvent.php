<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\ConfirmationToken;

final class UserConfirmedEvent extends DomainEvent
{
    public function __construct(
        public readonly ConfirmationToken $token,
        string $eventId = null,
        string $occurredOn = null
    ) {
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

    /**
     * @return array<string, ConfirmationToken>
     */
    public function toPrimitives(): array
    {
        return ['token' => $this->token];
    }
}
