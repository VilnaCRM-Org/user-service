<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\OpenApi\Builder\Parameter;
use App\Shared\OpenApi\Builder\ResponseBuilder;

final class InvalidClientCredentialsResponseFactory implements
    AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Invalid client credentials',
            [
                new Parameter(
                    'error',
                    'string',
                    'invalid_client'
                ),
                new Parameter(
                    'error_description',
                    'string',
                    'Client authentication failed'
                ),
                new Parameter(
                    'message',
                    'string',
                    'Client authentication failed'
                ),
            ]
        );
    }
}