<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\CreateBatchRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response as ResponseF;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UserBatchEndpointFactory implements AbstractEndpointFactory
{
    private string $endpointUri = '/users/batch';

    private Response $validationErrorResponse;
    private Response $usersCreatedResponse;
    private Response $badRequestResponse;
    private RequestBody $batchRequest;

    public function __construct(
        string $apiPrefix,
        ResponseF\BadRequestResponseFactory $badRequestFactory,
        ResponseF\ValidationErrorFactory $validationErrorFactory,
        ResponseF\UsersBatchCreatedResponseFactory $usersCreatedFactory,
        CreateBatchRequestFactory $batchRequestFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->badRequestResponse = $badRequestFactory->getResponse();
        $this->validationErrorResponse = $validationErrorFactory->getResponse();
        $this->usersCreatedResponse = $usersCreatedFactory->getResponse();
        $this->batchRequest = $batchRequestFactory->getRequest();
    }

    #[\Override]
    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPostOperation($openApi);
    }

    private function setPostOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPost = $pathItem->getPost();
        $openApi->getPaths()->addPath($this->endpointUri, $pathItem
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
            HttpResponse::HTTP_CREATED => $this->usersCreatedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $valResponse,
        ];
    }

    private function getPathItem(OpenApi $openApi): PathItem
    {
        return $openApi->getPaths()->getPath($this->endpointUri);
    }
}
