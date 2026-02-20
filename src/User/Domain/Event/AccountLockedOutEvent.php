<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class AccountLockedOutEvent extends AuthDomainEvent
{
    public function __construct(
        public readonly string $email,
        public readonly int $failedAttempts,
        public readonly int $lockoutDurationSeconds,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string|int> $body
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
            $body['email'],
            (int) $body['failedAttempts'],
            (int) $body['lockoutDurationSeconds'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @psalm-return 'user.account_locked_out'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.account_locked_out';
    }

    /**
     * @return array<int|string>
     *
     * @psalm-return array{email: string, failedAttempts: int, lockoutDurationSeconds: int}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
            'failedAttempts' => $this->failedAttempts,
            'lockoutDurationSeconds' => $this->lockoutDurationSeconds,
        ];
    }
}
