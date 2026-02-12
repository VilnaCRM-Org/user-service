<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class EmailChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $newEmail,
        public readonly string $oldEmail,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string> $body
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
            $body['newEmail'],
            $body['oldEmail'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'email.changed'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'email.changed';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{userId: string, newEmail: string, oldEmail: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'newEmail' => $this->newEmail,
            'oldEmail' => $this->oldEmail,
        ];
    }
}
