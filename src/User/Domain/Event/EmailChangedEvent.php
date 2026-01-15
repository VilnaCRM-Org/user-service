<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;

final class EmailChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly UserInterface $user,
        public readonly string $oldEmail,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, User|string> $body
     */
    #[\Override]
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($body['user'], $body['oldEmail'], $eventId, $occurredOn);
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'email.changed';
    }

    /**
     * @return array<string, User|string>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'user' => $this->user,
            'oldEmail' => $this->oldEmail,
        ];
    }
}
