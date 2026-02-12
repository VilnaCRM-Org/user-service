<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class RecoveryCodeUsedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly int $remainingCount,
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
            (int) $body['remainingCount'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.recovery_code_used'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.recovery_code_used';
    }

    /**
     * @return (int|string)[]
     *
     * @psalm-return array{userId: string, remainingCount: int}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'remainingCount' => $this->remainingCount,
        ];
    }
}
