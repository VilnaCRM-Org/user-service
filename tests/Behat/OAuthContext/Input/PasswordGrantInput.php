<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class PasswordGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        private string $client_id,
        private string $client_secret,
        private string $username,
        private string $password,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }
}
