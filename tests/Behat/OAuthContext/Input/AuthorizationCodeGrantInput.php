<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class AuthorizationCodeGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        public string $client_id,
        public string $client_secret,
        public string $redirect_uri,
        public string $code,
        public ?string $code_verifier = null,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }
}
