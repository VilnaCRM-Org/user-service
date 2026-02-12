<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class PasswordResetRequestedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $userEmail,
        public readonly string $token,
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
            $body['userEmail'],
            $body['token'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'user.password_reset_requested'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'user.password_reset_requested';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{userId: string, userEmail: string, token: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'userEmail' => $this->userEmail,
            'token' => $this->token,
        ];
    }
}
