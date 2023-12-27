<?php

namespace App\Shared\Infrastructure;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // Describing some standard responses
        $standardResponse500 = new Response(description: 'Internal server error', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                        'status' => ['type' => 'integer'],
                    ],
                ],
                'example' => [
                    'type' => '/errors/500',
                    'title' => 'An error occurred',
                    'detail' => 'Something went wrong',
                    'status' => 500,
                ],
            ],
        ]),);

        $standardResponse400 = new Response(description: 'Bad request', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                        'status' => ['type' => 'integer'],
                    ],
                ],
                'example' => [
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'The input data is misformatted.',
                    'status' => 400,
                ],
            ],
        ]),);

        $standardResponse404 = new Response(description: 'User not found', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                        'status' => ['type' => 'integer'],
                    ],
                ],
                'example' => [
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'User not found',
                    'status' => 404,
                ],
            ],
        ]),);

        $standardResponse422 = new Response(description: 'Validation error', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                        'violations' => ['type' => 'array'],
                        'status' => ['type' => 'integer'],
                    ],
                ],
                'example' => [
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'some_property: This value should not be blank.',
                    'violations' => [
                        'propertyPath' => 'some_property',
                        'message' => 'This value should not be blank.',
                        'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                    ],
                    'status' => 422,
                ],
            ],
        ]),);

        // Overriding User endpoints
        $pathItem = $openApi->getPaths()->getPath('/api/users/{id}/resend-confirmation-email');
        $operation = $pathItem->getPost();

        $UuidWithExamplePathParam = new Model\Parameter(name: 'id', in: 'path', description: 'User identifier', required: true,
            example: '2b10b7a3-67f0-40ea-a367-44263321592a');

        $openApi->getPaths()->addPath('/api/users/{id}/resend-confirmation-email', $pathItem->withPost(
            $operation
                ->withParameters([$UuidWithExamplePathParam])
                ->withDescription('Resends confirmation email')
                ->withSummary('Resends confirmation email')
                ->withRequestBody(new Model\RequestBody(content: new \ArrayObject([
                    'application/json' => [
                        'example' => '{}',
                    ],])))
                ->withResponses([200 => new Response(description: 'Email was send again', content: new \ArrayObject([
                    'application/json' => [
                        'example' => '',
                    ],
                ]),),
                    404 => $standardResponse404,
                    429 => new Response(description: 'Too many requests', content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => ['type' => 'string'],
                                    'title' => ['type' => 'string'],
                                    'detail' => ['type' => 'string'],
                                    'status' => ['type' => 'integer'],
                                ],
                            ],
                            'example' => [
                                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                                'title' => 'An error occurred',
                                'detail' => 'Cannot send new email till 05 Dec 2023 14:55:45',
                                'status' => 429,
                            ],
                        ],
                    ]),),
                ])
        ));

        $pathItem = $openApi->getPaths()->getPath('/api/users');
        $operationPost = $pathItem->getPost();
        $operationGet = $pathItem->getGet();

        $duplicateEmailResponse = new Response(description: 'Duplicate email', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                        'status' => ['type' => 'integer'],
                    ],
                ],
                'example' => [
                    'status' => 409,
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'user@example.com address is already registered. Please use a different email address or try logging in.',
                ],
            ],
        ]),);

        $openApi->getPaths()->addPath('/api/users', $pathItem->withPost(
            $operationPost
                ->withResponse(400, $standardResponse400)
                ->withResponse(409, $duplicateEmailResponse)
                ->withResponse(422, $standardResponse422))
            ->withGet($operationGet->withResponse(400, $standardResponse400)));

        $pathItem = $openApi->getPaths()->getPath('/api/users/{id}');
        $operationPut = $pathItem->getPut();
        $operationPatch = $pathItem->getPatch();
        $operationDelete = $pathItem->getDelete();
        $operationGet = $pathItem->getGet();

        $oldPasswordErrorResponse = new Response(description: 'Old password mismatch', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'integer'],
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                    ],
                ],
                'example' => [
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'Old password is invalid',
                    'status' => 410,
                ],
            ],]),);

        $openApi->getPaths()->addPath('/api/users/{id}', $pathItem->withPut(
            $operationPut->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standardResponse400)
                ->withResponse(404, $standardResponse404)
                ->withResponse(409, $duplicateEmailResponse)
                ->withResponse(410, $oldPasswordErrorResponse)
                ->withResponse(422, $standardResponse422)
        )->withPatch(
            $operationPatch->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standardResponse400)
                ->withResponse(404, $standardResponse404)
                ->withResponse(409, $duplicateEmailResponse)
                ->withResponse(410, $oldPasswordErrorResponse)
                ->withResponse(422, $standardResponse422)
        )->withDelete(
            $operationDelete->withParameters([$UuidWithExamplePathParam])
                ->withResponses([
                    204 => new Response(description: 'User resource deleted', content: new \ArrayObject([
                        'application/json' => [
                            'example' => '',
                        ],
                    ]),),
                    404 => $standardResponse404])
        )->withGet($operationGet->withParameters([$UuidWithExamplePathParam])
            ->withResponse(404, $standardResponse404)));

        // Customising confirm endpoint
        $pathItem = $openApi->getPaths()->getPath('/api/users/confirm');
        $operationPatch = $pathItem->getPatch();

        $openApi->getPaths()->addPath('/api/users/confirm', $pathItem->withPatch(
            $operationPatch->withDescription('Confirms the User')->withSummary('Confirms the User')
                ->withResponses([
                    200 => new Response(description: 'User confirmed', content: new \ArrayObject([
                        'application/json' => [
                            'example' => '',
                        ],]),),
                    404 => new Response(description: 'Token not found or expired', content: new \ArrayObject([
                        'application/json' => [
                            'example' => '',
                        ],]),),
                ],
                )
        ));

        // Adding OAuthDocs
        $unsupportedGrantTypeResponse = new Response(description: 'Unsupported grant type', content: new \ArrayObject([
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
                    'error_description' => 'The authorization grant type is not supported by the authorization server.',
                    'hint' => 'Check that all required parameters have been provided',
                    'message' => 'The authorization grant type is not supported by the authorization server.',
                ],
            ],]),);
        $invalidClientCredentialsResponse = new Response(description: 'Invalid client credentials', content: new \ArrayObject([
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
            ],]),);

        $openApi->getPaths()->addPath('/api/oauth/authorize',
            new Model\PathItem(summary: 'Requests for authorization code', description: 'Requests for authorization code',
                get: new Model\Operation(tags: ['OAuth'], responses: [302 => new Response(
                description: 'Redirect to the provided redirect URI with authorization code.', content: new \ArrayObject([
                'application/json' => [
                    'example' => '',
                ],]), headers: new \ArrayObject(['Location' => new Model\Header(description: 'The URI to redirect to for user authorization',
                schema: ['type' => 'string', 'format' => 'uri', 'example' => 'https://example.com/oauth/callback?code=e7f8c62113a47f7a5a9dca1f&state=af0ifjsldkj'])]),),
                400 => $unsupportedGrantTypeResponse,
                401 => $invalidClientCredentialsResponse], parameters: [
                new Model\Parameter(name: 'response_type', in: 'query', description: 'Response type',
                    required: true, example: 'code'),
                new Model\Parameter(name: 'client_id', in: 'query', description: 'Client ID',
                    required: true, example: 'dc0bc6323f16fecd4224a3860ca894c5'),
                new Model\Parameter(name: 'redirect_uri', in: 'query', description: 'Redirect uri',
                    required: true, example: 'https://example.com/oauth/callback'),
                new Model\Parameter(name: 'scope', in: 'query', description: 'Scope',
                    required: false, example: 'profile email'),
                new Model\Parameter(name: 'state', in: 'query', description: 'State',
                    required: false, example: 'af0ifjsldkj'),
            ])));

        $openApi->getPaths()->addPath('/api/oauth/token',
            new Model\PathItem(summary: 'Requests for access token', description: 'Requests for access token',
                post: new Model\Operation(tags: ['OAuth'], responses: [200 => new Response(description: 'Access token returned',
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
                    ],]),),
                400 => $unsupportedGrantTypeResponse,
                401 => $invalidClientCredentialsResponse], requestBody: new Model\RequestBody(
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
                    ],])))));

        // Adding 500 response to all endpoints
        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $operationGet = $pathItem->getGet();
            $operationPost = $pathItem->getPost();
            $operationPut = $pathItem->getPut();
            $operationPatch = $pathItem->getPatch();
            $operationDelete = $pathItem->getDelete();

            if ($operationGet) {
                $pathItem = $pathItem->withGet($operationGet->withResponse(500, $standardResponse500));
            }
            if ($operationPost) {
                $pathItem = $pathItem->withPost($operationPost->withResponse(500, $standardResponse500));
            }
            if ($operationPut) {
                $pathItem = $pathItem->withPut($operationPut->withResponse(500, $standardResponse500));
            }
            if ($operationPatch) {
                $pathItem = $pathItem->withPatch($operationPatch->withResponse(500, $standardResponse500));
            }
            if ($operationDelete) {
                $pathItem = $pathItem->withDelete($operationDelete->withResponse(500, $standardResponse500));
            }

            $openApi->getPaths()->addPath($path, $pathItem);
        }

        $openApi = $openApi->withServers([new Model\Server('https://localhost')]);

        return $openApi;
    }
}
