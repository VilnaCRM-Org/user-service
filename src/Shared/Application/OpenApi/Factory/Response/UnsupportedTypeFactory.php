<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

final class UnsupportedTypeFactory implements
    AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    #[\Override]
    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Unsupported grant type',
            [
                $this->getErrorParam(),
                $this->getErrorDescriptionParam(),
                $this->getHintParam(),
            ],
            []
        );
    }

    private function getErrorParam(): Parameter
    {
        return new Parameter(
            'error',
            'string',
            'unsupported_grant_type'
        );
    }

    private function getErrorDescriptionParam(): Parameter
    {
        return new Parameter(
            'error_description',
            'string',
            'The authorization grant type is not '.
            'supported by the authorization server.'
        );
    }

    private function getHintParam(): Parameter
    {
        return new Parameter(
            'hint',
            'string',
            'Check that all required parameters have been provided'
        );
    }
}
