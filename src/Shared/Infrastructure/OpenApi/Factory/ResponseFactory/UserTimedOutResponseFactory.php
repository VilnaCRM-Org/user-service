<?php

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserTimedOutResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Too many requests',
            [
                new Parameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10'),
                new Parameter('title', 'string', 'An error occurred'),
                new Parameter('detail', 'string', 'Cannot send new email till 2024-01-24T12:43:01+00:00'),
                new Parameter('status', 'integer', HttpResponse::HTTP_TOO_MANY_REQUESTS),
            ]
        );

    }
}
