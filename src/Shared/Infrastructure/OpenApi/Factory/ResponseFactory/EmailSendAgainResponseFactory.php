<?php

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;

class EmailSendAgainResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build('Email was send again');
    }
}
