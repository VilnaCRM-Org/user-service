<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use RuntimeException;

final class PasswordResetEmailSentEvent extends DomainEvent
{
    public function __construct(
        public readonly PasswordResetTokenInterface $token,
        public readonly string $email,
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
        throw new RuntimeException(
            'Cannot reconstruct PasswordResetEmailSentEvent from primitives.'
        );
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'user.password_reset_email_sent';
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'tokenValue' => $this->token->getTokenValue(),
            'userId' => $this->token->getUserID(),
            'email' => $this->email,
        ];
    }
}
