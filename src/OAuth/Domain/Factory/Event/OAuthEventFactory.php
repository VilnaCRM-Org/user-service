<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Factory\Event;

use App\OAuth\Domain\Event\OAuthUserCreatedEvent;
use App\OAuth\Domain\Event\OAuthUserSignedInEvent;

/**
 * @psalm-api
 */
final class OAuthEventFactory implements OAuthEventFactoryInterface
{
    #[\Override]
    public function createUserCreated(
        string $userId,
        string $email,
        string $provider,
        string $eventId
    ): OAuthUserCreatedEvent {
        return new OAuthUserCreatedEvent(
            $userId,
            $email,
            $provider,
            $eventId
        );
    }

    #[\Override]
    public function createUserSignedIn(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
        string $eventId
    ): OAuthUserSignedInEvent {
        return new OAuthUserSignedInEvent(
            $userId,
            $email,
            $provider,
            $sessionId,
            $eventId
        );
    }
}
