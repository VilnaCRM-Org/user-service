<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserNotFoundResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'User not found',
            [
                new Parameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10'),
                new Parameter('title', 'string', 'An error occurred'),
                new Parameter('detail', 'string', 'User not found'),
                new Parameter('status', 'integer', HttpResponse::HTTP_NOT_FOUND),
            ]
        );
    }
}
