<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class InternalServerErrorResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Internal server error',
            [
                new Parameter('type', 'string', '/errors/500'),
                new Parameter('title', 'string', 'An error occurred'),
                new Parameter('detail', 'string', 'Something went wrong'),
                new Parameter('status', 'integer', HttpResponse::HTTP_INTERNAL_SERVER_ERROR),
            ]
        );
    }
}
