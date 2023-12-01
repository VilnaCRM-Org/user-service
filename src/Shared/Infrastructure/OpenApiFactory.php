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

        $standartRespose400 = new Response(description: 'Invalid input', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                    ],
                ],
                'example' => [
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'The input data is misformatted.',
                ],
            ],
        ]), );

        $standartRespose404 = new Response(description: 'Entity not found', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                    ],
                ],
                'example' => [
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'Entity not found',
                ],
            ],
        ]), );

        $standartRespose422 = new Response(description: 'Validation error', content: new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                        'violations' => ['type' => 'array'],
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
                ->withRequestBody()
                ->withResponses([200 => new Response(description: 'Email was send again', content: new \ArrayObject([
                    'application/json' => [
                        'example' => '{}',
                    ],
                ]), ),
                    404 => $standartRespose404,
                    400 => new Response(description: 'Empty ID passed', content: new \ArrayObject([
                        'application/json' => [
                            'example' => [
                                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                                'title' => 'An error occurred',
                                'detail' => 'User ID cannot be empty',
                            ],
                        ],
                    ]), )])
        ));

        $pathItem = $openApi->getPaths()->getPath('/api/users');
        $operationPost = $pathItem->getPost();

        $openApi->getPaths()->addPath('/api/users', $pathItem->withPost(
            $operationPost->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standartRespose400)
                ->withResponse(422, $standartRespose422)));

        $pathItem = $openApi->getPaths()->getPath('/api/users/{id}');
        $operationPut = $pathItem->getPut();
        $operationPatch = $pathItem->getPatch();
        $operationDelete = $pathItem->getDelete();
        $operationGet = $pathItem->getGet();

        $openApi->getPaths()->addPath('/api/users/{id}', $pathItem->withPut(
            $operationPut->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standartRespose400)
                ->withResponse(404, $standartRespose404)
                ->withResponse(422, $standartRespose422)
        )->withPatch(
            $operationPatch->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standartRespose400)
                ->withResponse(404, $standartRespose404)
                ->withResponse(422, $standartRespose422)
        )->withDelete(
            $operationDelete->withParameters([$UuidWithExamplePathParam])
            ->withResponse(404, $standartRespose404)
        )->withGet($operationGet->withResponse(404, $standartRespose404)));

        $pathItem = $openApi->getPaths()->getPath('/api/users/confirm');
        $operationPatch = $pathItem->getPatch();

        $openApi->getPaths()->addPath('/api/users/confirm', $pathItem->withPatch(
            $operationPost->withParameters([$UuidWithExamplePathParam])
                ->withResponse(400, $standartRespose400)
                ->withResponse(404, $standartRespose404)
                ->withResponse(422, $standartRespose422)));

        return $openApi;
    }
}
