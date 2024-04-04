<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class ClientCredentialsGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        public string $client_id,
        public string $client_secret,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }
}
