<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\ConfirmationToken;

final class ConfirmationEmailSentEvent extends DomainEvent
{
    public function __construct(
        public readonly ConfirmationToken $token,
        public readonly string $emailAddress,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string|ConfirmationToken> $body
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
        return 'confirmation_email.send';
    }

    /**
     * @return array<string, string|ConfirmationToken>
     */
    public function toPrimitives(): array
    {
        return [
            'emailAddress' => $this->emailAddress,
            'token' => $this->token,
        ];
    }
}
