<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final readonly class ObtainAuthorizeCodeInput
{
    private string $response_type;

    public function __construct(
        private string $client_id,
        private string $redirect_uri
    ) {
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
