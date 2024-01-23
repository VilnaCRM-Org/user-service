<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseParameter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InternalServerErrorResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Internal server error',
            [
                new ResponseParameter('type', 'string', '/errors/500'),
                new ResponseParameter('title', 'string', 'An error occurred'),
                new ResponseParameter('detail', 'string', 'Something went wrong'),
                new ResponseParameter('status', 'integer', HttpResponse::HTTP_INTERNAL_SERVER_ERROR),
            ]
        );
    }
}
