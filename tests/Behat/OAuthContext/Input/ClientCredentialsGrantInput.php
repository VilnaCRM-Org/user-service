<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class ClientCredentialsGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        private string $client_id,
        private string $client_secret,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }
}
