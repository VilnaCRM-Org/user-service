<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TwoFactorCompletedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly string $method,
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
            $body['sessionId'],
            $body['ipAddress'],
            $body['userAgent'],
            $body['method'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.two_factor_completed'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.two_factor_completed';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{userId: string, sessionId: string, ipAddress: string, userAgent: string, method: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'ipAddress' => $this->ipAddress,
            'userAgent' => $this->userAgent,
            'method' => $this->method,
        ];
    }
}
