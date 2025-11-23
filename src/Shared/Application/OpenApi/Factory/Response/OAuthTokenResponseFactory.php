<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;

final class OAuthTokenResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    #[\Override]
    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'OAuth access token response',
            [
                $this->getTokenTypeParam(),
                $this->getExpiresInParam(),
                $this->getAccessTokenParam(),
                $this->getRefreshTokenParam(),
            ],
            []
        );
    }

    private function getTokenTypeParam(): Parameter
    {
        return new Parameter(
            'token_type',
            'string',
            'Bearer'
        );
    }

    private function getExpiresInParam(): Parameter
    {
        return new Parameter('expires_in', 'integer', 3600);
    }

    private function getAccessTokenParam(): Parameter
    {
        return new Parameter(
            'access_token',
            'string',
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdW'
        );
    }

    private function getRefreshTokenParam(): Parameter
    {
        return new Parameter(
            'refresh_token',
            'string',
            'df9b4ae7ce2e1e8f2a3c1b4d'
        );
    }
}
