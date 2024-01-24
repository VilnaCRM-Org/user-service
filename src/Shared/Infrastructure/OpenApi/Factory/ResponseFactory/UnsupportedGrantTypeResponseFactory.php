<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;

class UnsupportedGrantTypeResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Unsupported grant type',
            [
                new Parameter('error', 'string', 'unsupported_grant_type'),
                new Parameter('error_description', 'string', 'The authorization grant 
                        type is not supported by the authorization server.'),
                new Parameter('hint', 'string', 'Check that all required 
                            parameters have been provided'),
                new Parameter('message', 'string', 'The authorization grant type is not 
                        supported by the authorization server.'),
            ]
        );
    }
}
