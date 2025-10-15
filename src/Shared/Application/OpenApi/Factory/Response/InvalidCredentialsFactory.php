<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;

final class InvalidCredentialsFactory implements
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
}
