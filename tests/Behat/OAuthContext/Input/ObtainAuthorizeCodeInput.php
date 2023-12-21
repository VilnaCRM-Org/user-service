<?php

namespace App\Tests\Behat\OAuthContext\Input;

readonly class ObtainAuthorizeCodeInput
{
    public string $response_type;

    public function __construct(public string $client_id, public string $redirect_uri)
    {
        $this->response_type = 'code';
    }

    public function toUriParams(): string
    {
        $queryParams = [
            'response_type' => $this->response_type,
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
        ];

        return http_build_query($queryParams);
    }
}
