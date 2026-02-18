<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UnauthorizedResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    #[\Override]
    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Unauthorized',
            [
                $this->getTypeParam(),
                $this->getTitleParam(),
                $this->getStatusParam(),
                $this->getDetailParam(),
            ],
            [],
            'application/problem+json'
        );
    }

    private function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            'about:blank'
        );
    }

    private function getTitleParam(): Parameter
    {
        return new Parameter(
            'title',
            'string',
            'Unauthorized'
        );
    }

    private function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            HttpResponse::HTTP_UNAUTHORIZED
        );
    }

    private function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'Authentication required.'
        );
    }
}
