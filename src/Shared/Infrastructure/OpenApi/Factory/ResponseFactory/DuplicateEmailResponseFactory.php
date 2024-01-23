<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseParameter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DuplicateEmailResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Duplicate email',
            [
                new ResponseParameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10'),
                new ResponseParameter('title', 'string', 'An error occurred'),
                new ResponseParameter('detail', 'string', 'user@example.com address is already registered. Please use a different email address or try logging in.'),
                new ResponseParameter('status', 'integer', HttpResponse::HTTP_CONFLICT),
            ]
        );
    }
}
