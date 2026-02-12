<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class UserSignedInEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $sessionId,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly bool $twoFactorUsed,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string|bool> $body
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
            $body['sessionId'],
            $body['ipAddress'],
            $body['userAgent'],
            $body['twoFactorUsed'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.signed_in'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.signed_in';
    }

    /**
     * @return (bool|string)[]
     *
     * @psalm-return array{userId: string, email: string, sessionId: string, ipAddress: string, userAgent: string, twoFactorUsed: bool}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'sessionId' => $this->sessionId,
            'ipAddress' => $this->ipAddress,
            'userAgent' => $this->userAgent,
            'twoFactorUsed' => $this->twoFactorUsed,
        ];
    }
}
