<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

/**
 * @psalm-suppress UnusedProperty - Client credentials stored for Authorization header, not sent in request body
 */
final class RefreshTokenGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        private string $client_id,
        private string $client_secret,
        private string $refresh_token,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }

    /**
     * @return (null|string)[]
     *
     * @psalm-return array{grant_type: null|string, refresh_token: string}
     */
    #[\Override]
    public function toArray(): array
    {
        // Client credentials are sent via Authorization header, not in body
        return [
            'grant_type' => $this->getGrantType(),
            'refresh_token' => $this->refresh_token,
        ];
    }
}
