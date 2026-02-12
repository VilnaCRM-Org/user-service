<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Emitted when a user session is revoked (logout, password change, etc.).
 *
 * AC: NFR-33 - Audit logging for auth events
 */
final class SessionRevokedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly string $reason,  // "logout", "password_change", "two_factor_enabled", etc.
        string $eventId = '',
        ?string $occurredOn = null,
    ) {
        parent::__construct($eventId ? $eventId : uniqid('event_', true), $occurredOn);
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
            $body['sessionId'],
            $body['reason'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.session.revoked'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.session.revoked';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{userId: string, sessionId: string, reason: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'reason' => $this->reason,
        ];
    }
}
