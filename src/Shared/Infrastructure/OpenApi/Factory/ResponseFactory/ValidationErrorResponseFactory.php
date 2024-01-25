<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ValidationErrorResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Internal server error',
            [
                $this->getTypeParam(),
                $this->getTitleParam(),
                $this->getDetailParam(),
                $this->getViolationsParam(),
                $this->getStatusParam(),
            ]
        );
    }

    public function getViolationsParam(): Parameter
    {
        return new Parameter('violations', 'array', [
            'propertyPath' => 'some_property',
            'message' => 'This value should not be blank.',
            'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
        ]);
    }

    public function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            '/errors/500'
        );
    }

    public function getTitleParam(): Parameter
    {
        return new Parameter(
            'title',
            'string',
            'An error occurred'
        );
    }

    public function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'some_property: This value should not be blank.'
        );
    }

    public function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
