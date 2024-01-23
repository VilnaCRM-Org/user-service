<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\BadRequestResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\DuplicateEmailResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserNotFoundResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\ValidationErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParametrizedUserEndpointFactory implements AbstractEndpointFactory
{
    public function __construct(
        private ValidationErrorResponseFactory $validationErrorResponseFactory,
        private DuplicateEmailResponseFactory $duplicateEmailResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private UserNotFoundResponseFactory $userNotFoundResponseFactory
    ) {
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath('/api/users/{id}');
        $operationPut = $pathItem->getPut();
        $operationPatch = $pathItem->getPatch();
        $operationDelete = $pathItem->getDelete();
        $operationGet = $pathItem->getGet();

        $UuidWithExamplePathParam = new Model\Parameter(
            name: 'id',
            in: 'path',
            description: 'User identifier',
            required: true,
            example: '2b10b7a3-67f0-40ea-a367-44263321592a'
        );

        $duplicateEmailResponse =
            $this->duplicateEmailResponseFactory->getResponse();

        $standardResponse400 = $this->badRequestResponseFactory->getResponse();

        $standardResponse404 =
            $this->userNotFoundResponseFactory->getResponse();

        $standardResponse422 =
            $this->validationErrorResponseFactory->getResponse();

        $openApi->getPaths()->addPath('/api/users/{id}', $pathItem->withPut(
            $operationPut->withParameters([$UuidWithExamplePathParam])
                ->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)
                ->withResponse(HttpResponse::HTTP_NOT_FOUND, $standardResponse404)
                ->withResponse(HttpResponse::HTTP_CONFLICT, $duplicateEmailResponse)
                ->withResponse(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $standardResponse422)
        )->withPatch(
            $operationPatch->withParameters([$UuidWithExamplePathParam])
                ->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)
                ->withResponse(HttpResponse::HTTP_NOT_FOUND, $standardResponse404)
                ->withResponse(HttpResponse::HTTP_CONFLICT, $duplicateEmailResponse)
                ->withResponse(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $standardResponse422)
        )->withDelete(
            $operationDelete->withParameters([$UuidWithExamplePathParam])
                ->withResponses([
                    HttpResponse::HTTP_NO_CONTENT => new Response(
                        description: 'User resource deleted',
                        content: new \ArrayObject([
                            'application/json' => [
                                'example' => '',
                            ],
                        ]),
                    ),
                    HttpResponse::HTTP_NOT_FOUND => $standardResponse404])
        )->withGet($operationGet->withParameters([$UuidWithExamplePathParam])
            ->withResponse(HttpResponse::HTTP_NOT_FOUND, $standardResponse404)));
    }
}
