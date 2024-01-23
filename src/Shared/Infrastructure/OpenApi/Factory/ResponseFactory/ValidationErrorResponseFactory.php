<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseParameter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ValidationErrorResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Validation error',
            [
                new ResponseParameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10'),
                new ResponseParameter('title', 'string', 'An error occurred'),
                new ResponseParameter('detail', 'string', 'some_property: This value should not be blank.'),
                new ResponseParameter('violations', 'array', [
                    'propertyPath' => 'some_property',
                    'message' => 'This value should not be blank.',
                    'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                ]),
                new ResponseParameter('status', 'integer', HttpResponse::HTTP_UNPROCESSABLE_ENTITY),
            ]
        );
    }
}
