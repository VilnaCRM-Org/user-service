<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EndpointFactory;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthTokenEndpointFactory implements AbstractEndpointFactory
{
    public function createEndpoint(OpenApi $openApi): void
    {
        $unsupportedGrantTypeResponse = new Response(
            description: 'Unsupported grant type',
            content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'error' => ['type' => 'string'],
                        'error_description' => ['type' => 'string'],
                        'hint' => ['type' => 'string'],
                        'message' => ['type' => 'string'],
                    ],
                ],
                'example' => [
                    'error' => 'unsupported_grant_type',
                    'error_description' => 'The authorization grant type is not 
                        supported by the authorization server.',
                    'hint' => 'Check that all required 
                    parameters have been provided',
                    'message' => 'The authorization grant type is not 
                        supported by the authorization server.',
                ],
            ],]),
        );

        $invalidClientCredentialsResponse = new Response(
            description: 'Invalid client credentials',
            content: new \ArrayObject([
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'error' => ['type' => 'string'],
                            'error_description' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'example' => [
                        'error' => 'invalid_client',
                        'error_description' => 'Client authentication failed',
                        'message' => 'Client authentication failed',
                    ],
                ],
            ]),
        );

        $openApi->getPaths()->addPath(
            '/api/oauth/token',
            new Model\PathItem(
                summary: 'Requests for access token',
                description: 'Requests for access token',
                post: new Model\Operation(tags: ['OAuth'], responses: [
                    HttpResponse::HTTP_OK => new Response(
                        description: 'Access token returned',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'token_type' => ['type' => 'string'],
                                        'expires_in' => ['type' => 'integer'],
                                        'access_token' => ['type' => 'string'],
                                        'refresh_token' => ['type' => 'string'],
                                    ],
                                ],
                                'example' => [
                                    'token_type' => 'Bearer',
                                    'expires_in' => 3600,
                                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdW',
                                    'refresh_token' => 'df9b4ae7ce2e1e8f2a3c1b4d',
                                ],
                            ],]),
                    ),
                    HttpResponse::HTTP_BAD_REQUEST => $unsupportedGrantTypeResponse,
                    HttpResponse::HTTP_UNAUTHORIZED => $invalidClientCredentialsResponse,
                ], requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'grant_type' => ['type' => 'string'],
                                    'client_id' => ['type' => 'string'],
                                    'client_secret' => ['type' => 'string'],
                                    'redirect_uri' => ['type' => 'string'],
                                    'code' => ['type' => 'string'],
                                    'refresh_token' => ['type' => 'string'],
                                ],
                            ],
                            'example' => [
                                'grant_type' => 'authorization_code',
                                'client_id' => 'dc0bc6323f16fecd4224a3860ca894c5',
                                'client_secret' => '8897b24436ac63e457fbd7d0bd5b678686c0cb214ef92fa9e8464fc777ec5',
                                'redirect_uri' => 'https://example.com/oauth/callback',
                                'code' => 'e7f8c62113a47f7a5a9dca1f',
                            ],
                        ],
                    ])
                ))
            )
        );
    }
}
