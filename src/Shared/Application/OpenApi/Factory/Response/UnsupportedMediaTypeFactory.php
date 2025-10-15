<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UnsupportedMediaTypeFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Unsupported media type',
            [
                $this->getTypeParam(),
                $this->getTitleParam(),
                $this->getDetailParam(),
                $this->getStatusParam(),
            ],
            [],
            'application/problem+json'
        );
    }

    private function getTypeParam(): Parameter
    {
        return new Parameter('type', 'string', '/errors/415');
    }

    private function getTitleParam(): Parameter
    {
        return new Parameter('title', 'string', 'Unsupported Media Type');
    }

    private function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'The "Content-Type" header must exist.'
        );
    }

    private function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            HttpResponse::HTTP_UNSUPPORTED_MEDIA_TYPE
        );
    }
}
