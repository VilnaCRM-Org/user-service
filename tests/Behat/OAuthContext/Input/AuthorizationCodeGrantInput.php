<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class AuthorizationCodeGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        private string $client_id,
        private string $client_secret,
        private string $redirect_uri,
        private string $code,
        private ?string $code_verifier = null,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }
}
