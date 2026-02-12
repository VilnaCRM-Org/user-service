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
     *
     * @return self
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

    /**
     * @return string
     *
     * @psalm-return 'user.updated'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.updated';
    }

    /**
     * @return (null|string)[]
     *
     * @psalm-return array{userId: string, email: string, previousEmail: null|string}
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
