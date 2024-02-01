<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\OpenApi\Builder\Parameter;
use App\Shared\OpenApi\Builder\ResponseBuilder;
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
                $this->getTypeParam(),
                $this->getTitleParam(),
                $this->getDetailParam(),
                $this->getStatusParam(),
            ]
        );
    }

    public function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            'https://tools.ietf.org/html/rfc2616#section-10'
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
            'user@example.com address is already registered. '.
            'Please use a different email address or try logging in.'
        );
    }

    public function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            HttpResponse::HTTP_CONFLICT
        );
    }
}
