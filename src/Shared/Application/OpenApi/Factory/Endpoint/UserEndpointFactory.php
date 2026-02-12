<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\CreateUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UsersReturnedFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserEndpointFactory implements EndpointFactoryInterface
{
    private string $endpointUri = '/users';

    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $userCreatedResponse;
    private RequestBody $createUserRequest;
    private Response $usersReturnedResponse;

    public function __construct(
        string $apiPrefix,
        ValidationErrorFactory $validationErrorResponseFactory,
        BadRequestResponseFactory $badRequestResponseFactory,
        UserCreatedResponseFactory $userCreatedResponseFactory,
        CreateUserRequestFactory $createUserRequestFactory,
        UsersReturnedFactory $usersReturnedResponseFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->validationErrorResponse =
            $validationErrorResponseFactory->getResponse();
        $this->badRequestResponse = $badRequestResponseFactory->getResponse();
        $this->userCreatedResponse = $userCreatedResponseFactory->getResponse();
        $this->createUserRequest = $createUserRequestFactory->getRequest();
        $this->usersReturnedResponse = $usersReturnedResponseFactory->getResponse();
    }

    #[\Override]
    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath($this->endpointUri);
        $operationPost = $pathItem->getPost();
        $operationGet = $pathItem->getGet();

        $openApi->getPaths()->addPath($this->endpointUri, $pathItem
            ->withPost(
                $operationPost
                    ->withResponses($this->getPostResponses())
                    ->withRequestBody($this->createUserRequest)
            )
            ->withGet($operationGet->withResponses(
                $this->getGetResponses()
            )));
    }

    /**
     * @return Response[]
     *
     * @psalm-return array{201: Response, 400: Response, 422: Response}
     */
    private function getPostResponses(): array
    {
        $valResponse = $this->validationErrorResponse;
        return [
            HttpResponse::HTTP_CREATED => $this->userCreatedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $valResponse,
        ];
    }

    /**
     * @return Response[]
     *
     * @psalm-return array{400: Response, 200: Response}
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_OK => $this->usersReturnedResponse,
        ];
    }
}
