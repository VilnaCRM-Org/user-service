<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Factory\Event;

use App\OAuth\Domain\Event\OAuthUserCreatedEvent;
use App\OAuth\Domain\Event\OAuthUserSignedInEvent;

interface OAuthEventFactoryInterface
{
    public function createUserCreated(
        string $userId,
        string $email,
        string $provider,
        string $eventId
    ): OAuthUserCreatedEvent;

    public function createUserSignedIn(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
        string $eventId
    ): OAuthUserSignedInEvent;
}
