<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class OAuthTokenRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build(
            [
                $this->getGrantTypeParam(),
                $this->getClientIdParam(),
                $this->getClientSecretParam(),
                $this->getRedirectUriParam(),
                $this->getCodeParam(),
                $this->getRefreshTokenParam(),
            ]
        );
    }

    private function getRedirectUriParam(): Parameter
    {
        return new Parameter(
            'redirect_uri',
            'string',
            'https://example.com/oauth/callback'
        );
    }

    private function getCodeParam(): Parameter
    {
        return new Parameter(
            'code',
            'string',
            'e7f8c62113a47f7a5a9dca1f'
        );
    }

    private function getRefreshTokenParam(): Parameter
    {
        return new Parameter(
            'refresh_token',
            'string',
            'f7a5a9dca1fe7f8c62113a47'
        );
    }

    private function getGrantTypeParam(): Parameter
    {
        return new Parameter(
            'grant_type',
            'string',
            'authorization_code'
        );
    }

    private function getClientIdParam(): Parameter
    {
        return new Parameter(
            'client_id',
            'string',
            'dc0bc6323f16fecd4224a3860ca894c5'
        );
    }

    private function getClientSecretParam(): Parameter
    {
        return new Parameter(
            'client_secret',
            'string',
            '8897b24436ac63e457fbd7d0bd5b678686c0cb214ef92fa9e8464fc7'
        );
    }
}
