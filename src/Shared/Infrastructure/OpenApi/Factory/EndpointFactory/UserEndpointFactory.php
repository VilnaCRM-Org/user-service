<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\BadRequestResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\DuplicateEmailResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\ValidationErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users';

    public function __construct(
        private ValidationErrorResponseFactory $validationErrorResponseFactory,
        private DuplicateEmailResponseFactory $duplicateEmailResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory
    ) {
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operationPost = $pathItem->getPost();
        $operationGet = $pathItem->getGet();

        $duplicateEmailResponse =
            $this->duplicateEmailResponseFactory->getResponse();

        $standardResponse422 =
            $this->validationErrorResponseFactory->getResponse();

        $standardResponse400 = $this->badRequestResponseFactory->getResponse();

        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem->withPost(
            $operationPost
                ->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)
                ->withResponse(HttpResponse::HTTP_CONFLICT, $duplicateEmailResponse)
                ->withResponse(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $standardResponse422)
        )
            ->withGet($operationGet->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)));
    }
}
