<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\User;

class EmailChangedEvent extends DomainEvent
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
        return 'email.changed';
    }

    /**
     * @return array<string, User>
     */
    public function toPrimitives(): array
    {
        return [
            'user' => $this->user,
        ];
    }
}
