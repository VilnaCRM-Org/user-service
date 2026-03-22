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

    /**
     * @return array<string, array|bool|float|int|object|string|null>
     */
    #[\Override]
    public function toArray(): array
    {
        $data = [
            'grant_type' => $this->getGrantType(),
            'redirect_uri' => $this->redirect_uri,
            'code' => $this->code,
        ];

        // For public clients (empty secret), include client_id in body
        if ($this->client_secret === '' || $this->client_secret === null) {
            $data['client_id'] = $this->client_id;
        }

        if ($this->code_verifier !== null) {
            $data['code_verifier'] = $this->code_verifier;
        }

        return $data;
    }
}
