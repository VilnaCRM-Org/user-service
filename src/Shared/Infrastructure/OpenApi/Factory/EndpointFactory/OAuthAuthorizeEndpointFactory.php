<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\InvalidClientCredentialsResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\OAuthRedirectResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UnsupportedGrantTypeResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthAuthorizeEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/oauth/authorize';

    private Response $unsupportedResponse;
    private Response $invalidResponse;
    private Response $redirectResponse;

    public function __construct(
        private UnsupportedGrantTypeResponseFactory $unsupportedFactory,
        private InvalidClientCredentialsResponseFactory $invalidCredsFactory,
        private OAuthRedirectResponseFactory $redirectResponseFactory
    ) {
        $this->unsupportedResponse =
            $this->unsupportedFactory->getResponse();
        $this->invalidResponse =
            $this->invalidCredsFactory->getResponse();
        $this->redirectResponse = $this->redirectResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            new Model\PathItem(
                summary: 'Requests for authorization code',
                description: 'Requests for authorization code',
                get: new Model\Operation(
                    tags: ['OAuth'],
                    responses: $this->getResponses(),
                    parameters: [
                        $this->getQueryParams(),
                    ]
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
            HttpResponse::HTTP_FOUND => $this->redirectResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->unsupportedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->invalidResponse,
        ];
    }

    /**
     * @return array<Model\Parameter>
     */
    private function getQueryParams(): array
    {
        $params = [];
        $params[] = $this->getResponseTypeQueryParam();
        $params[] = $this->getClientIdQueryParam();
        $params[] = $this->getRedirectUriQueryParam();
        $params[] = $this->getScopeQueryParam();
        $params[] = $this->getStateQueryParam();

        return $params;
    }

    private function getStateQueryParam(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'state',
            in: 'query',
            description: 'State',
            required: false,
            example: 'af0ifjsldkj'
        );
    }

    private function getScopeQueryParam(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'scope',
            in: 'query',
            description: 'Scope',
            required: false,
            example: 'profile email'
        );
    }

    private function getRedirectUriQueryParam(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'redirect_uri',
            in: 'query',
            description: 'Redirect uri',
            required: true,
            example: 'https://example.com/oauth/callback'
        );
    }

    private function getClientIdQueryParam(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'client_id',
            in: 'query',
            description: 'Client ID',
            required: true,
            example: 'dc0bc6323f16fecd4224a3860ca894c5'
        );
    }

    private function getResponseTypeQueryParam(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'response_type',
            in: 'query',
            description: 'Response type',
            required: true,
            example: 'code'
        );
    }
}
