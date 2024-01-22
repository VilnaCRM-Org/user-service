<?php

namespace App\Tests\Behat\OAuthContext\Input;

class AuthorizationCodeGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        public string $client_id,
        public string $client_secret,
        public string $redirect_uri,
        public string $code,
        string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }
}
