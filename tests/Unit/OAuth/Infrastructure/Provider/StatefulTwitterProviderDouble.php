<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use League\OAuth2\Client\Token\AccessToken;
use Smolblog\OAuth2\Client\Provider\Twitter;

final class StatefulTwitterProviderDouble extends Twitter
{
    public function __construct()
    {
    }

    /**
     * @param string $grant
     * @param array<string, string> $options
     */
    #[\Override]
    public function getAccessToken($grant, array $options = []): AccessToken
    {
        $fallbackCode = is_string($grant) ? $grant : 'authorization_code';
        $code = $options['code'] ?? $fallbackCode;

        return new AccessToken([
            'access_token' => sprintf(
                '%s|%s',
                $code,
                $this->pkceVerifier ?? 'none',
            ),
        ]);
    }
}
