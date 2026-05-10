<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Publisher;

interface OAuthPublisherInterface
{
    public function publishUserCreated(
        string $userId,
        string $email,
        string $provider
    ): void;

    public function publishUserSignedIn(
        string $userId,
        string $email,
        string $provider,
        string $sessionId
    ): void;
}
