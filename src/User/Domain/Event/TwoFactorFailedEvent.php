<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TwoFactorFailedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $pendingSessionId,
        public readonly string $ipAddress,
        public readonly string $reason,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string> $body
     */
    #[\Override]
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self(
            $body['pendingSessionId'],
            $body['ipAddress'],
            $body['reason'],
            $eventId,
            $occurredOn
        );
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'user.two_factor_failed';
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'pendingSessionId' => $this->pendingSessionId,
            'ipAddress' => $this->ipAddress,
            'reason' => $this->reason,
        ];
    }
}
