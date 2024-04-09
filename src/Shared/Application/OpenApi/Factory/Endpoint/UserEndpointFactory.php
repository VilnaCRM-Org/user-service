<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users';

    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $userCreatedResponse;

    public function __construct(
        private ValidationErrorFactory $validationErrorResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private UserCreatedResponseFactory $userCreatedResponseFactory
    ) {
        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();
        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();
        $this->userCreatedResponse =
            $this->userCreatedResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operationPost = $pathItem->getPost();
        $operationGet = $pathItem->getGet();

        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPost(
                $operationPost
                    ->withResponses($this->getPostResponses())
            )
            ->withGet($operationGet->withResponse(
                HttpResponse::HTTP_BAD_REQUEST,
                $this->badRequestResponse
            )));
    }

    /**
     * @return array<int,Response>
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
}
