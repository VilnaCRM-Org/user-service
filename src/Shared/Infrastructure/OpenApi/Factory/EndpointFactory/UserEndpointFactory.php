<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\BadRequestResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\DuplicateEmailResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\ValidationErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users';

    private Response $duplicateEmailResponse;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;

    public function __construct(
        private ValidationErrorResponseFactory $validationErrorResponseFactory,
        private DuplicateEmailResponseFactory $duplicateEmailResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory
    ) {
        $this->duplicateEmailResponse =
            $this->duplicateEmailResponseFactory->getResponse();
        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();
        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();
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
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_CONFLICT => $this->duplicateEmailResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ];
    }
}
