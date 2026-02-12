<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class SignInFailedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $email,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly string $reason,
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
            $body['email'],
            $body['ipAddress'],
            $body['userAgent'],
            $body['reason'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.sign_in_failed'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.sign_in_failed';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{email: string, ipAddress: string, userAgent: string, reason: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
            'ipAddress' => $this->ipAddress,
            'userAgent' => $this->userAgent,
            'reason' => $this->reason,
        ];
    }
}
