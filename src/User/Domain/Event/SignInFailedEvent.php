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
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, string> $body
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
            $eventId,
            $occurredOn
        );
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'user.sign_in_failed';
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
            'ipAddress' => $this->ipAddress,
            'userAgent' => $this->userAgent,
        ];
    }
}
