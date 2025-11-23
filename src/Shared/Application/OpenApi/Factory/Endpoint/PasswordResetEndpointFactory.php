<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request as RequestF;

final class PasswordResetEndpointFactory implements AbstractEndpointFactory
{
    private string $requestEndpointUri = '/reset-password';
    private string $confirmEndpointUri = '/reset-password/confirm';

    private RequestBody $requestPasswordResetRequest;
    private RequestBody $confirmPasswordResetRequest;

    public function __construct(
        string $apiPrefix,
        RequestF\RequestPasswordResetRequestFactory $requestResetFactory,
        RequestF\ConfirmPasswordResetRequestFactory $confirmResetFactory
    ) {
        $this->requestEndpointUri = $apiPrefix . $this->requestEndpointUri;
        $this->confirmEndpointUri = $apiPrefix . $this->confirmEndpointUri;
        $this->requestPasswordResetRequest =
            $requestResetFactory->getRequest();
        $this->confirmPasswordResetRequest =
            $confirmResetFactory->getRequest();
    }

    #[\Override]
    public function createEndpoint(OpenApi $openApi): void
    {
        $this->customiseRequestPasswordResetEndpoint($openApi);
        $this->customiseConfirmPasswordResetEndpoint($openApi);
    }

    private function customiseRequestPasswordResetEndpoint(
        OpenApi $openApi
    ): void {
        $pathItem = $this->getPathItem($openApi, $this->requestEndpointUri);
        $operationPost = $pathItem->getPost();

        $openApi->getPaths()->addPath(
            $this->requestEndpointUri,
            $pathItem->withPost(
                $operationPost->withRequestBody(
                    $this->requestPasswordResetRequest
                )
            )
        );
    }

    private function customiseConfirmPasswordResetEndpoint(
        OpenApi $openApi
    ): void {
        $pathItem = $this->getPathItem($openApi, $this->confirmEndpointUri);
        $operationPost = $pathItem->getPost();

        $openApi->getPaths()->addPath(
            $this->confirmEndpointUri,
            $pathItem->withPost(
                $operationPost->withRequestBody(
                    $this->confirmPasswordResetRequest
                )
            )
        );
    }

    private function getPathItem(OpenApi $openApi, string $endpoint): PathItem
    {
        return $openApi->getPaths()->getPath($endpoint);
    }
}
