<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class DuplicateEmailResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Duplicate email',
            [
                new Parameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10'),
                new Parameter('title', 'string', 'An error occurred'),
                new Parameter('detail', 'string', 'user@example.com address is already registered. Please use a different email address or try logging in.'),
                new Parameter('status', 'integer', HttpResponse::HTTP_CONFLICT),
            ]
        );
    }
}
