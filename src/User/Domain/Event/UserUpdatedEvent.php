<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class UserUpdatedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly ?string $previousEmail,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string|null> $body
     */
    #[\Override]
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self(
            $body['userId'],
            $body['email'],
            $body['previousEmail'] ?? null,
            $eventId,
            $occurredOn
        );
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'user.updated';
    }

    /**
     * @return array<string, string|null>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'previousEmail' => $this->previousEmail,
        ];
    }
}
