<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;

final class GoogleAccessTokenEchoProvider extends Google
{
    public function __construct()
    {
        parent::__construct([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'redirectUri' => 'https://example.com/callback',
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
            'access_token' => $this->getPkceCode() ?? 'no-verifier',
        ]);
    }
}
