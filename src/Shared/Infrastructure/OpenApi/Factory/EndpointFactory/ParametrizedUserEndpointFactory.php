<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\BadRequestResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\DuplicateEmailResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserDeletedResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserNotFoundResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\ValidationErrorResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\UriParamFactory\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParametrizedUserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users/{id}';

    private Parameter $uuidWithExamplePathParam;
    private Response $duplicateEmailResponse;
    private Response $badRequestResponse;
    private Response $userNotFoundResponse;
    private Response $validationErrorResponse;
    private Response $userDeletedResponse;

    public function __construct(
        private ValidationErrorResponseFactory $validationErrorResponseFactory,
        private DuplicateEmailResponseFactory $duplicateEmailResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private UserDeletedResponseFactory $deletedResponseFactory,
        private UuidUriParameterFactory $parameterFactory
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->duplicateEmailResponse =
            $this->duplicateEmailResponseFactory->getResponse();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();

        $this->userNotFoundResponse =
            $this->userNotFoundResponseFactory->getResponse();

        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->userDeletedResponse =
            $this->deletedResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPutOperation($openApi);
        $this->setPatchOperation($openApi);
        $this->setGetOperation($openApi);
        $this->setDeleteOperation($openApi);
    }

    private function setPutOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPut = $pathItem->getPut();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPut(
                $operationPut
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($this->getUpdateResponses())
            ));
    }

    private function setPatchOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPatch = $pathItem->getPatch();
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem
                ->withPatch(
                    $operationPatch
                        ->withParameters([$this->uuidWithExamplePathParam])
                        ->withResponses($this->getUpdateResponses())
                )
        );
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
            HttpResponse::HTTP_CONFLICT => $this->duplicateEmailResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ];
    }

    private function setDeleteOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationDelete = $pathItem->getDelete();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withDelete(
                $operationDelete
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses(
                        [
                            HttpResponse::HTTP_NO_CONTENT => $this->userDeletedResponse,
                            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
                        ]
                    )
            ));
    }

    private function setGetOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationGet = $pathItem->getGet();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withGet(
                $operationGet->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponse(
                        HttpResponse::HTTP_NOT_FOUND,
                        $this->userNotFoundResponse
                    )
            ));
    }

    private function getPathItem(OpenApi $openApi): PathItem
    {
        return $openApi->getPaths()->getPath(self::ENDPOINT_URI);
    }
}
