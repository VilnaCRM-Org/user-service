<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Token\AccessToken;

final class StatefulFacebookProviderDouble extends Facebook
{
    public function __construct()
    {
    }

    /**
     * @param string $pkceCode
     *
     * @return $this
     */
    #[\Override]
    public function setPkceCode($pkceCode)
    {
        parent::setPkceCode($pkceCode);

        return $this;
    }

    /**
     * @param string $grant
     * @param array<string, string> $params
     */
    #[\Override]
    public function getAccessToken($grant = 'authorization_code', array $params = []): AccessToken
    {
        return new AccessToken([
            'access_token' => sprintf(
                '%s|%s',
                $params['code'],
                $this->getPkceCode() ?? 'none',
            ),
        ]);
    }
}
