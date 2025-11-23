<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Response\InvalidCredentialsFactory;
use App\Shared\Application\OpenApi\Factory\Response\OAuthRedirectFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedTypeFactory;
use ArrayObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthAuthEndpointFactory implements AbstractEndpointFactory
{
    private string $endpointUri = '/oauth/authorize';

    private Response $unsupportedResponse;
    private Response $invalidResponse;
    private Response $redirectResponse;
    private Response $redirectTargetResponse;
    private OAuthAuthorizeQueryParametersFactory $queryParametersFactory;

    public function __construct(
        string $apiPrefix,
        UnsupportedTypeFactory $unsupportedFactory,
        InvalidCredentialsFactory $invalidCredsFactory,
        OAuthRedirectFactory $redirectResponseFactory,
        OAuthAuthorizeQueryParametersFactory $queryParametersFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->unsupportedResponse = $unsupportedFactory->getResponse();
        $this->invalidResponse = $invalidCredsFactory->getResponse();
        $this->redirectResponse = $redirectResponseFactory->getResponse();
        $this->redirectTargetResponse = $this->createRedirectTargetResponse();
        $this->queryParametersFactory = $queryParametersFactory;
    }

    #[\Override]
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
            operationId: 'oauth_authorize_get',
            summary: 'Start OAuth authorization flow',
            description: $this->authorizeDescription(),
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
            HttpResponse::HTTP_OK => $this->redirectTargetResponse,
            HttpResponse::HTTP_FOUND => $this->redirectResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->unsupportedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->invalidResponse,
        ];
    }

    /**
     * @return array<int,Model\Parameter>
     */
    private function getQueryParams(): array
    {
        return $this->queryParametersFactory->create();
    }

    private function authorizeDescription(): string
    {
        return implode(
            ' ',
            [
                'Redirects the resource owner to grant access',
                'and returns an authorization code.',
            ]
        );
    }

    private function createRedirectTargetResponse(): Response
    {
        $htmlExample = <<<'HTML'
<!doctype html>
<html lang="en">
<head><title>Example Domain</title></head>
<body><h1>Example Domain</h1></body>
</html>
HTML;

        return new Response(
            description: 'Redirect target served HTML response.',
            content: new ArrayObject([
                'text/html' => new Model\MediaType(
                    example: \strtr($htmlExample, ["\n" => ''])
                ),
            ])
        );
    }
}
