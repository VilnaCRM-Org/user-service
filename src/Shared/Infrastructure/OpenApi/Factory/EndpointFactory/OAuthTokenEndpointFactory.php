<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\RequestFactory\OAuthTokenRequestFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\InvalidClientCredentialsResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\OAuthTokenReturnedResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UnsupportedGrantTypeResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthTokenEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/oauth/token';

    private Response $tokenResponse;
    private Response $invalidResponse;
    private Response $unsupportedResponse;

    public function __construct(
        private UnsupportedGrantTypeResponseFactory $unsupportedFactory,
        private InvalidClientCredentialsResponseFactory $invalidCredsFactory,
        private OAuthTokenReturnedResponseFactory $tokenReturnedResponseFactory,
        private OAuthTokenRequestFactory $tokenRequestFactory
    ) {
        $this->tokenResponse =
            $this->tokenReturnedResponseFactory->getResponse();
        $this->invalidResponse = $this->invalidCredsFactory->getResponse();
        $this->unsupportedResponse = $this->unsupportedFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
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
