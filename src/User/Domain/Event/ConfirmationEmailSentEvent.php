<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class ConfirmationEmailSentEvent extends DomainEvent
{
    public function __construct(
        public readonly string $tokenValue,
        public readonly string $emailAddress,
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
            $body['tokenValue'],
            $body['emailAddress'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'confirmation_email.send'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'confirmation_email.send';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{emailAddress: string, tokenValue: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'emailAddress' => $this->emailAddress,
            'tokenValue' => $this->tokenValue,
        ];
    }
}
