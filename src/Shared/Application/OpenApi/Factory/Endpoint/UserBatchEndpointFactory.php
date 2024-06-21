<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\CreateBatchRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\UsersReturnedFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserBatchEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users/batch';

    private Response $validationErrorResponse;
    private Response $usersReturnedResponse;
    private RequestBody $batchRequest;

    public function __construct(
        private ValidationErrorFactory $validationErrorResponseFactory,
        private UsersReturnedFactory $usersReturnedResponseFactory,
        private CreateBatchRequestFactory $batchRequestFactory
    ) {
        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();
        $this->usersReturnedResponse =
            $this->usersReturnedResponseFactory->getResponse();
        $this->batchRequest = $this->batchRequestFactory->getRequest();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPostOperation($openApi);
    }

    private function setPostOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPost = $pathItem->getPost();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPost(
                $operationPost
                    ->withResponses($this->getPostResponses())
                    ->withRequestBody($this->batchRequest)
            ));
    }

    /**
     * @return array<int,Response>
     */
    private function getPostResponses(): array
    {
        $valResponse = $this->validationErrorResponse;
        return [
            HttpResponse::HTTP_CREATED => $this->usersReturnedResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $valResponse,
        ];
    }

    private function getPathItem(OpenApi $openApi): PathItem
    {
        return $openApi->getPaths()->getPath(self::ENDPOINT_URI);
    }
}
