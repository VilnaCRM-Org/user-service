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
    private OAuthTokenRequestFactory $tokenRequestFactory;

    public function __construct(
        string $apiPrefix,
        UnsupportedTypeFactory $unsupportedFactory,
        InvalidCredentialsFactory $invalidCredsFactory,
        OAuthTokenResponseFactory $tokenReturnedResponseFactory,
        OAuthTokenRequestFactory $tokenRequestFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->tokenResponse =
            $tokenReturnedResponseFactory->getResponse();
        $this->invalidResponse = $invalidCredsFactory->getResponse();
        $this->unsupportedResponse = $unsupportedFactory->getResponse();
        $this->tokenRequestFactory = $tokenRequestFactory;
    }

    #[\Override]
    public function createEndpoint(OpenApi $openApi): void
    {
        $openApi->getPaths()->addPath(
            $this->endpointUri,
            new PathItem(
                summary: 'Requests for access token',
                description: 'Requests for access token',
                post: new Operation(
                    operationId: 'oauth_token_post',
                    summary: 'Exchange authorization data for tokens',
                    description: $this->tokenDescription(),
                    tags: ['OAuth'],
                    responses: $this->getResponses(),
                    requestBody: $this->tokenRequestFactory->getRequest(),
                    security: []
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

    private function tokenDescription(): string
    {
        return implode(
            ' ',
            [
                'Exchanges an authorization code, password credentials,',
                'or refresh token for OAuth access and refresh tokens.',
            ]
        );
    }
}
