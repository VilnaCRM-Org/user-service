<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\OpenApi\Factory\Response\DuplicateEmailResponseFactory;
use App\Shared\OpenApi\Factory\Response\UserCreatedResponseFactory;
use App\Shared\OpenApi\Factory\Response\ValidationErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users';

    private Response $duplicateEmailResponse;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $userCreatedResponse;

    public function __construct(
        private ValidationErrorResponseFactory $validationErrorResponseFactory,
        private DuplicateEmailResponseFactory $duplicateEmailResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private UserCreatedResponseFactory $userCreatedResponseFactory
    ) {
        $this->duplicateEmailResponse =
            $this->duplicateEmailResponseFactory->getResponse();
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
            HttpResponse::HTTP_CONFLICT => $this->duplicateEmailResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $valResponse,
        ];
    }
}
