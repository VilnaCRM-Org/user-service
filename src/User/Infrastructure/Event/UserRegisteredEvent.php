<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\User;

class UserRegisteredEvent extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        string $eventId = null,
        string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): DomainEvent
    {
        return new self($body['user'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.registered';
    }

    public function toPrimitives(): array
    {
        return [
            'user' => $this->user,
        ];
    }
}
