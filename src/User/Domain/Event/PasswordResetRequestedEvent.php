<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\ConfirmationToken;

final class PasswordResetRequestedEvent extends DomainEvent
{
    public function __construct(
        public ConfirmationToken $token,
        public string $emailAddress,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string> $body
     */
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self(
            $body['token'],
            $body['emailAddress'],
            $eventId,
            $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'password.reset.requested';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [
            'token' => $this->token,
            'emailAddress' => $this->emailAddress,
        ];
    }
}
