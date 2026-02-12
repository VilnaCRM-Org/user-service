<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class AllSessionsRevokedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $reason,
        public readonly int $revokedCount,
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
            $body['reason'],
            (int) $body['revokedCount'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.all_sessions_revoked'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.all_sessions_revoked';
    }

    /**
     * @return (int|string)[]
     *
     * @psalm-return array{userId: string, reason: string, revokedCount: int}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'reason' => $this->reason,
            'revokedCount' => $this->revokedCount,
        ];
    }
}
