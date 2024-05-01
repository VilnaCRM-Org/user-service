<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\ReplaceUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\UpdateUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamUserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users/{id}';

    private Parameter $uuidWithExamplePathParam;
    private Response $badRequestResponse;
    private Response $userNotFoundResponse;
    private Response $validationErrorResponse;
    private Response $userDeletedResponse;
    private Response $userUpdatedResponse;
    private Response $userReturnedResponse;

    private RequestBody $replaceUserRequest;

    private RequestBody $updateUserRequest;

    public function __construct(
        private ValidationErrorFactory $validationErrorResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private UserDeletedResponseFactory $deletedResponseFactory,
        private UuidUriParameterFactory $parameterFactory,
        private UserUpdatedResponseFactory $userUpdatedResponseFactory,
        private UserReturnedResponseFactory $userReturnedResponseFactory,
        private ReplaceUserRequestFactory $replaceUserRequestFactory,
        private UpdateUserRequestFactory $updateUserRequestFactory
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();

        $this->userNotFoundResponse =
            $this->userNotFoundResponseFactory->getResponse();

        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->userDeletedResponse =
            $this->deletedResponseFactory->getResponse();

        $this->userUpdatedResponse =
            $this->userUpdatedResponseFactory->getResponse();

        $this->userReturnedResponse =
            $this->userReturnedResponseFactory->getResponse();

        $this->replaceUserRequest =
            $this->replaceUserRequestFactory->getRequest();

        $this->updateUserRequest =
            $this->updateUserRequestFactory->getRequest();
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
                    ->withRequestBody($this->replaceUserRequest)
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
                        ->withRequestBody($this->updateUserRequest)
                )
        );
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        $valResponse = $this->validationErrorResponse;
        return [
            HttpResponse::HTTP_OK => $this->userUpdatedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $valResponse,
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
                    ->withResponses($this->getDeleteResponses())
            ));
    }

    /**
     * @return array<int,Response>
     */
    private function getDeleteResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->userDeletedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
        ];
    }

    private function setGetOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationGet = $pathItem->getGet();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withGet(
                $operationGet->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses(
                        $this->getGetResponses()
                    )
            ));
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
            HttpResponse::HTTP_OK => $this->userReturnedResponse,
        ];
    }

    private function getPathItem(OpenApi $openApi): PathItem
    {
        return $openApi->getPaths()->getPath(self::ENDPOINT_URI);
    }
}
