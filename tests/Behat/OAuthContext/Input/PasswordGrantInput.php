<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

/**
 * @psalm-suppress UnusedProperty - Client credentials stored for Authorization header, not sent in request body
 */
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

    /**
     * @return array<string, array|bool|float|int|object|string|null>
     */
    #[\Override]
    public function toArray(): array
    {
        // Client credentials are sent via Authorization header, not in body
        return [
            'grant_type' => $this->getGrantType(),
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
