<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BadRequestResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Bad request',
            [
                new Parameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10'),
                new Parameter('title', 'string', 'An error occurred'),
                new Parameter('detail', 'string', 'The input data is misformatted.'),
                new Parameter('status', 'integer', HttpResponse::HTTP_BAD_REQUEST),
            ]
        );
    }
}
