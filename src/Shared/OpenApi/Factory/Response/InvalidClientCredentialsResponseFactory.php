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
                $this->getErrorParam(),
                $this->getErrorDescriptionParam(),
                $this->getMessageParam(),
            ],
            []
        );
    }

    private function getErrorParam(): Parameter
    {
        return new Parameter(
            'error',
            'string',
            'invalid_client'
        );
    }

    private function getErrorDescriptionParam(): Parameter
    {
        return new Parameter(
            'error_description',
            'string',
            'Client authentication failed'
        );
    }

    private function getMessageParam(): Parameter
    {
        return new Parameter(
            'message',
            'string',
            'Client authentication failed'
        );
    }
}
