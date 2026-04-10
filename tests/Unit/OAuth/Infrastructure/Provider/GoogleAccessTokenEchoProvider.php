<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;

final class GoogleAccessTokenEchoProvider extends Google
{
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        private readonly string $fallbackToken,
    ) {
        parent::__construct([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
        ]);
    }

    /**
     * @param mixed $grant
     * @param array<array-key, mixed> $options
     */
    #[\Override]
    public function getAccessToken($grant, array $options = []): AccessToken
    {
        return new AccessToken([
            'access_token' => $this->getPkceCode() ?? $this->fallbackToken,
        ]);
    }
}
