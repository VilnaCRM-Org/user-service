<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class RefreshTokenRotatedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $userId,
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
            $body['sessionId'],
            $body['userId'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.refresh_token_rotated'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.refresh_token_rotated';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{sessionId: string, userId: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'userId' => $this->userId,
        ];
    }
}
