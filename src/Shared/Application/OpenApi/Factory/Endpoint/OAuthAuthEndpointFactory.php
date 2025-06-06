<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Builder\QueryParameterBuilder;
use App\Shared\Application\OpenApi\Factory\Response\InvalidCredentialsFactory;
use App\Shared\Application\OpenApi\Factory\Response\OAuthRedirectFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedTypeFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthAuthEndpointFactory implements AbstractEndpointFactory
{
    private string $endpointUri = '/oauth/authorize';

    private Response $unsupportedResponse;
    private Response $invalidResponse;
    private Response $redirectResponse;

    public function __construct(
        string $apiPrefix,
        private UnsupportedTypeFactory $unsupportedFactory,
        private InvalidCredentialsFactory $invalidCredsFactory,
        private OAuthRedirectFactory $redirectResponseFactory,
        private QueryParameterBuilder $queryParameterBuilder
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->unsupportedResponse = $this->unsupportedFactory->getResponse();
        $this->invalidResponse = $this->invalidCredsFactory->getResponse();
        $this->redirectResponse = $this->redirectResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $openApi->getPaths()->addPath(
            $this->endpointUri,
            $this->createPathItem()
        );
    }

    private function createPathItem(): Model\PathItem
    {
        return new Model\PathItem(
            summary: 'Requests for authorization code',
            description: 'Requests for authorization code',
            get: $this->createOperation()
        );
    }

    private function createOperation(): Model\Operation
    {
        return new Model\Operation(
            tags: ['OAuth'],
            responses: $this->getResponses(),
            parameters: $this->getQueryParams()
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
        return [
            $this->getResponseTypeQueryParam(),
            $this->getClientIdQueryParam(),
            $this->getRedirectUriQueryParam(),
            $this->getScopeQueryParam(),
            $this->getStateQueryParam(),
        ];
    }

    private function getStateQueryParam(): Model\Parameter
    {
        return $this->queryParameterBuilder->build(
            'state',
            'State',
            false,
            'af0ifjsldkj',
            'string'
        );
    }

    private function getScopeQueryParam(): Model\Parameter
    {
        return $this->queryParameterBuilder->build(
            'scope',
            'Scope',
            false,
            'profile email',
            'string'
        );
    }

    private function getRedirectUriQueryParam(): Model\Parameter
    {
        return $this->queryParameterBuilder->build(
            'redirect_uri',
            'Redirect uri',
            true,
            'https://example.com/oauth/callback',
            'string'
        );
    }

    private function getClientIdQueryParam(): Model\Parameter
    {
        return $this->queryParameterBuilder->build(
            'client_id',
            'Client ID',
            true,
            'dc0bc6323f16fecd4224a3860ca894c5',
            'string'
        );
    }

    private function getResponseTypeQueryParam(): Model\Parameter
    {
        return $this->queryParameterBuilder->build(
            'response_type',
            'Response type',
            true,
            'code',
            'string'
        );
    }
}
