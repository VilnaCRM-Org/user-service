<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\InternalServerErrorResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\InvalidClientCredentialsResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UnsupportedGrantTypeResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthAuthorizeEndpointFactory implements AbstractEndpointFactory
{
    public function __construct(
        private UnsupportedGrantTypeResponseFactory $unsupportedFactory,
        private InvalidClientCredentialsResponseFactory $invalidCredsFactory
    ) {
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $unsupportedGrantTypeResponse =
            $this->unsupportedFactory->getResponse();

        $invalidClientCredentialsResponse =
            $this->invalidCredsFactory->getResponse();

        $openApi->getPaths()->addPath(
            '/api/oauth/authorize',
            new Model\PathItem(
                summary: 'Requests for authorization code',
                description: 'Requests for authorization code',
                get: new Model\Operation(tags: ['OAuth'], responses: [
                    HttpResponse::HTTP_FOUND => new Response(
                        description: 'Redirect to the provided redirect URI with authorization code.',
                        content: new \ArrayObject([
                            'application/json' => [
                                'example' => '',
                            ], ]),
                        headers: new \ArrayObject(['Location' => new Model\Header(
                            description: 'The URI to redirect to for user authorization',
                            schema: ['type' => 'string', 'format' => 'uri', 'example' => 'https://example.com/oauth/callback?code=e7f8c62113a47f7a5a9dca1f&state=af0ifjsldkj']
                        )]),
                    ),
                    HttpResponse::HTTP_BAD_REQUEST => $unsupportedGrantTypeResponse,
                    HttpResponse::HTTP_UNAUTHORIZED => $invalidClientCredentialsResponse], parameters: [
                    new Model\Parameter(
                        name: 'response_type',
                        in: 'query',
                        description: 'Response type',
                        required: true,
                        example: 'code'
                    ),
                    new Model\Parameter(
                        name: 'client_id',
                        in: 'query',
                        description: 'Client ID',
                        required: true,
                        example: 'dc0bc6323f16fecd4224a3860ca894c5'
                    ),
                    new Model\Parameter(
                        name: 'redirect_uri',
                        in: 'query',
                        description: 'Redirect uri',
                        required: true,
                        example: 'https://example.com/oauth/callback'
                    ),
                    new Model\Parameter(
                        name: 'scope',
                        in: 'query',
                        description: 'Scope',
                        required: false,
                        example: 'profile email'
                    ),
                    new Model\Parameter(
                        name: 'state',
                        in: 'query',
                        description: 'State',
                        required: false,
                        example: 'af0ifjsldkj'
                    ),
                ])
            )
        );
    }
}
