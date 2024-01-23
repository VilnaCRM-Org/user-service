<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseParameter;

class InvalidClientCredentialsResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Invalid client credentials',
            [
                new ResponseParameter('error', 'string', 'invalid_client'),
                new ResponseParameter('error_description', 'string', 'Client authentication failed'),
                new ResponseParameter('message', 'string', 'Client authentication failed'),
            ]
        );
    }
}
