<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Factory\Request\ReplaceUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\UpdateUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamUserEndpointFactory implements AbstractEndpointFactory
{
    private const OPERATION_DEFINITIONS = [
        [
            'method' => 'put',
            'parameterProperty' => 'updateUserPathParam',
            'responsesProperty' => 'updateResponses',
            'requestProperty' => 'replaceUserRequest',
        ],
        [
            'method' => 'patch',
            'parameterProperty' => 'updateUserPathParam',
            'responsesProperty' => 'updateResponses',
            'requestProperty' => 'updateUserRequest',
        ],
        [
            'method' => 'get',
            'parameterProperty' => 'baseUserPathParam',
            'responsesProperty' => 'getResponses',
        ],
        [
            'method' => 'delete',
            'parameterProperty' => 'deleteUserPathParam',
            'responsesProperty' => 'deleteResponses',
        ],
    ];

    private string $endpointUri = '/users/{id}';

    private Parameter $baseUserPathParam;
    private Parameter $updateUserPathParam;
    private Parameter $deleteUserPathParam;
    private Response $badRequestResponse;
    private Response $userNotFoundResponse;
    private Response $validationErrorResponse;
    private Response $userDeletedResponse;
    private Response $userUpdatedResponse;
    private Response $userReturnedResponse;

    private RequestBody $replaceUserRequest;

    private RequestBody $updateUserRequest;

    /**
     * @var array<int,Response>
     */
    private array $updateResponses = [];

    /**
     * @var array<int,Response>
     */
    private array $deleteResponses = [];

    /**
     * @var array<int,Response>
     */
    private array $getResponses = [];

    public function __construct(
        string $apiPrefix,
        private ParamUserResponseProvider $responseProvider,
        private UuidUriParameterFactory $parameterFactory,
        private ReplaceUserRequestFactory $replaceUserRequestFactory,
        private UpdateUserRequestFactory $updateUserRequestFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;

        $this->configurePathParameters();
        $this->configureResponses();
        $this->configureRequests();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        foreach (self::OPERATION_DEFINITIONS as $definition) {
            $parameter = $this->{$definition['parameterProperty']};
            $responses = $this->{$definition['responsesProperty']};
            $requestProperty = $definition['requestProperty'] ?? null;
            $requestBody = $requestProperty !== null
                ? $this->{$requestProperty}
                : null;

            $this->addOperation(
                $openApi,
                $definition['method'],
                $parameter,
                $responses,
                $requestBody
            );
        }
    }

    /**
     * @param array<int,Response> $responses
     */
    private function addOperation(
        OpenApi $openApi,
        string $method,
        Parameter $parameter,
        array $responses,
        ?RequestBody $requestBody = null
    ): void {
        $pathItem = $openApi->getPaths()->getPath($this->endpointUri);
        $getter = 'get' . ucfirst($method);
        $operation = $pathItem->$getter()
            ->withParameters([$parameter])
            ->withResponses($responses);

        if ($requestBody !== null) {
            $operation = $operation->withRequestBody($requestBody);
        }

        $setter = 'with' . ucfirst($method);
        $openApi->getPaths()->addPath(
            $this->endpointUri,
            $pathItem->$setter($operation)
        );
    }

    private function configurePathParameters(): void
    {
        $this->baseUserPathParam = $this->parameterFactory->getParameterFor(
            SchemathesisFixtures::USER_ID
        );
        $this->updateUserPathParam = $this->parameterFactory->getParameterFor(
            SchemathesisFixtures::UPDATE_USER_ID
        );
        $this->deleteUserPathParam = $this->parameterFactory->getParameterFor(
            SchemathesisFixtures::DELETE_USER_ID
        );
    }

    private function configureResponses(): void
    {
        $this->initializeResponseObjects();
        $this->initializeResponseCollections();
    }

    private function initializeResponseObjects(): void
    {
        $this->badRequestResponse = $this->responseProvider->badRequest();
        $this->userNotFoundResponse = $this->responseProvider->userNotFound();
        $this->validationErrorResponse = $this->responseProvider->validationError();
        $this->userDeletedResponse = $this->responseProvider->userDeleted();
        $this->userUpdatedResponse = $this->responseProvider->userUpdated();
        $this->userReturnedResponse = $this->responseProvider->userReturned();
    }

    private function initializeResponseCollections(): void
    {
        $validationError = $this->validationErrorResponse;

        $this->updateResponses = [
            HttpResponse::HTTP_OK => $this->userUpdatedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $validationError,
        ];

        $this->deleteResponses = [
            HttpResponse::HTTP_NO_CONTENT => $this->userDeletedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
        ];

        $this->getResponses = [
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
            HttpResponse::HTTP_OK => $this->userReturnedResponse,
        ];
    }

    private function configureRequests(): void
    {
        $this->replaceUserRequest =
            $this->replaceUserRequestFactory->getRequest();
        $this->updateUserRequest =
            $this->updateUserRequestFactory->getRequest();
    }
}
