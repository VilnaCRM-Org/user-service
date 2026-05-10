<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class OAuthUserCreatedEvent extends OAuthDomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $provider,
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
            $body['email'],
            $body['provider'],
            $eventId,
            $occurredOn
        );
    }

    /**
     * @psalm-return 'oauth.user_created'
     */
    #[\Override]
    public static function eventName(): string
    {
        return 'oauth.user_created';
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{userId: string, email: string, provider: string}
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'provider' => $this->provider,
        ];
    }
}
