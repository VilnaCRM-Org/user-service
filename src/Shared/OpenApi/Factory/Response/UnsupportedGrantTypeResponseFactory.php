<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\OpenApi\Builder\Parameter;
use App\Shared\OpenApi\Builder\ResponseBuilder;

final class UnsupportedGrantTypeResponseFactory implements
    AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Unsupported grant type',
            [
                $this->getErrorParam(),
                $this->getErrorDescriptionParam(),
                $this->getHintParam(),
                $this->getMessageParam(),
            ]
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

    private function getMessageParam(): Parameter
    {
        return new Parameter(
            'message',
            'string',
            'The authorization grant type is not '.
            'supported by the authorization server.'
        );
    }
}
