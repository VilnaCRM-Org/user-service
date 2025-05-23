<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\OAuthTokenRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\InvalidCredentialsFactory;
use App\Shared\Application\OpenApi\Factory\Response\OAuthTokenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedTypeFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthTokenEndpointFactory implements AbstractEndpointFactory
{
    private string $endpointUri = '/oauth/token';

    private Response $tokenResponse;
    private Response $invalidResponse;
    private Response $unsupportedResponse;

    public function __construct(
        string $apiPrefix,
        private UnsupportedTypeFactory $unsupportedFactory,
        private InvalidCredentialsFactory $invalidCredsFactory,
        private OAuthTokenResponseFactory $tokenReturnedResponseFactory,
        private OAuthTokenRequestFactory $tokenRequestFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->tokenResponse =
            $this->tokenReturnedResponseFactory->getResponse();
        $this->invalidResponse = $this->invalidCredsFactory->getResponse();
        $this->unsupportedResponse = $this->unsupportedFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $openApi->getPaths()->addPath(
            $this->endpointUri,
            new PathItem(
                summary: 'Requests for access token',
                description: 'Requests for access token',
                post: new Operation(
                    tags: ['OAuth'],
                    responses: $this->getResponses(),
                    requestBody: $this->tokenRequestFactory->getRequest(),
                )
            )
        );
    }

    /**
     * @return array<int,Response>
     */
    private function getResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->tokenResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->unsupportedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->invalidResponse,
        ];
    }
}
