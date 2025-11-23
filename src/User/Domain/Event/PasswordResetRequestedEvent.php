<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\UserInterface;

final class PasswordResetRequestedEvent extends DomainEvent
{
    public function __construct(
        public readonly UserInterface $user,
        public readonly string $token,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, UserInterface|string> $body
     */
    #[\Override]
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($body['user'], $body['token'], $eventId, $occurredOn);
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'user.password_reset_requested';
    }

    /**
     * @return array<string, UserInterface|string>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'user' => $this->user,
            'token' => $this->token,
        ];
    }
}
