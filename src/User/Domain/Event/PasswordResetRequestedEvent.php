<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\User\Domain\Entity\UserInterface;
use RuntimeException;

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
     * @param array<string, string> $body
     */
    #[\Override]
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        throw new RuntimeException(
            'Cannot reconstruct PasswordResetRequestedEvent from primitives.'
        );
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'user.password_reset_requested';
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function toPrimitives(): array
    {
        return [
            'userId' => $this->user->getId(),
            'userEmail' => $this->user->getEmail(),
            'token' => $this->token,
        ];
    }
}
