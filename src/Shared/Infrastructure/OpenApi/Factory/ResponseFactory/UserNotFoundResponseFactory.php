<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseParameter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserNotFoundResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'User not found',
            [
                new ResponseParameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10'),
                new ResponseParameter('title', 'string', 'An error occurred'),
                new ResponseParameter('detail', 'string', 'User not found'),
                new ResponseParameter('status', 'integer', HttpResponse::HTTP_NOT_FOUND),
            ]
        );
    }
}
