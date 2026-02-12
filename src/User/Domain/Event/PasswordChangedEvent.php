<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class PasswordChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $email,
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
        return new self($body['email'], $eventId, $occurredOn);
    }

    /**
     * @return string
     *
     * @psalm-return 'password.changed'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'password.changed';
    }

    /**
     * @return string[]
     *
     * @psalm-return array{email: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}
