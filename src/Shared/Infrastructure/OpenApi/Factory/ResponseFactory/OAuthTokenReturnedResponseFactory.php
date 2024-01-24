<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;

class OAuthTokenReturnedResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Unsupported grant type',
            [
                new Parameter('token_type', 'string', 'Bearer'),
                new Parameter('expires_in', 'integer', 3600),
                new Parameter('access_token', 'string', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdW'),
                new Parameter('refresh_token', 'string', 'df9b4ae7ce2e1e8f2a3c1b4d'),
            ]
        );
    }
}
