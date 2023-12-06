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
        ]), );

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
        ]), );

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
        ]), );

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
        ]), );

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
                    ], ])))
                ->withResponses([200 => new Response(description: 'Email was send again', content: new \ArrayObject([
                    'application/json' => [
                        'example' => '',
                    ],
                ]), ),
                    404 => $standardResponse404,
                    429 => new Response(description: 'User was timed out', content: new \ArrayObject([
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
                    ]), ),
                ])
        ));

        $pathItem = $openApi->getPaths()->getPath('/api/users');
        $operationPost = $pathItem->getPost();
        $operationGet = $pathItem->getGet();

        $openApi->getPaths()->addPath('/api/users', $pathItem->withPost(
            $operationPost
                ->withResponse(400, $standardResponse400)
                ->withResponse(409, new Response(description: 'Duplicate email', content: new \ArrayObject([
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
                ]), ))
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
            ], ]), );

        $openApi->getPaths()->addPath('/api/users/{id}', $pathItem->withPut(
            $operationPut->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standardResponse400)
                ->withResponse(404, $standardResponse404)
                ->withResponse(422, $standardResponse422)
                ->withResponse(410, $oldPasswordErrorResponse)
        )->withPatch(
            $operationPatch->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standardResponse400)
                ->withResponse(404, $standardResponse404)
                ->withResponse(422, $standardResponse422)
                ->withResponse(410, $oldPasswordErrorResponse)
        )->withDelete(
            $operationDelete->withParameters([$UuidWithExamplePathParam])
                ->withResponses([
                    204 => new Response(description: 'User resource deleted', content: new \ArrayObject([
                        'application/json' => [
                            'example' => '',
                        ],
                    ]), ),
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
                        ], ]), ),
                    404 => new Response(description: 'Token not found or expired', content: new \ArrayObject([
                        'application/json' => [
                            'example' => '',
                        ], ]), ),
                ],
                )
        ));

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

        return $openApi;
    }
}
