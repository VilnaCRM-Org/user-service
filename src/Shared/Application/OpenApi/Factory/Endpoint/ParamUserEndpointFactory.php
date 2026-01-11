<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\ReplaceUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\UpdateUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use App\Shared\Application\Provider\OpenApi\ParamUserResponseProvider;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamUserEndpointFactory implements EndpointFactoryInterface
{
    private const OPERATIONS = ['put', 'patch', 'get', 'delete'];

    private string $endpointUri = '/users/{id}';

    /** @var array<string, Parameter> */
    private array $pathParameters = [];

    /** @var array<string, array<int, Response>> */
    private array $operationResponses = [];

    /** @var array<string, RequestBody> */
    private array $requestBodies = [];

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

    #[\Override]
    public function createEndpoint(OpenApi $openApi): void
    {
        foreach (self::OPERATIONS as $method) {
            $parameter = $this->pathParameters[$method];
            $responses = $this->operationResponses[$method];
            $requestBody = $this->requestBodies[$method] ?? null;

            $this->addOperation(
                $openApi,
                $method,
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
        $this->pathParameters = [
            'put' => $this->parameterFactory->getParameterFor(
                SchemathesisFixtures::UPDATE_USER_ID
            ),
            'patch' => $this->parameterFactory->getParameterFor(
                SchemathesisFixtures::UPDATE_USER_ID
            ),
            'get' => $this->parameterFactory->getParameterFor(
                SchemathesisFixtures::USER_ID
            ),
            'delete' => $this->parameterFactory->getParameterFor(
                SchemathesisFixtures::DELETE_USER_ID
            ),
        ];
    }

    private function configureResponses(): void
    {
        $mutationResponses = $this->userMutationResponses();
        $readResponses = $this->userReadResponses();
        $deleteResponses = $this->userDeleteResponses();

        $this->operationResponses = [
            'put' => $mutationResponses,
            'patch' => $mutationResponses,
            'get' => $readResponses,
            'delete' => $deleteResponses,
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function userMutationResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->responseProvider->userUpdated(),
            HttpResponse::HTTP_BAD_REQUEST => $this->responseProvider->badRequest(),
            HttpResponse::HTTP_NOT_FOUND => $this->responseProvider->userNotFound(),
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->responseProvider->validationError(),
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function userReadResponses(): array
    {
        return [
            HttpResponse::HTTP_NOT_FOUND => $this->responseProvider->userNotFound(),
            HttpResponse::HTTP_OK => $this->responseProvider->userReturned(),
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function userDeleteResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->responseProvider->userDeleted(),
            HttpResponse::HTTP_NOT_FOUND => $this->responseProvider->userNotFound(),
        ];
    }

    private function configureRequests(): void
    {
        $this->requestBodies = [
            'put' => $this->replaceUserRequestFactory->getRequest(),
            'patch' => $this->updateUserRequestFactory->getRequest(),
        ];
    }
}
