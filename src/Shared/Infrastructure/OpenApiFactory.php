<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\EndpointFactory\AbstractEndpointFactory;
use App\Shared\Infrastructure\EndpointFactory\ConfirmUserEndpointFactory;
use App\Shared\Infrastructure\EndpointFactory\OAuthAuthorizeEndpointFactory;
use App\Shared\Infrastructure\EndpointFactory\OAuthTokenEndpointFactory;
use App\Shared\Infrastructure\EndpointFactory\ResendEmailEndpointFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    private AbstractEndpointFactory $endpointFactory;

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
                    'status' => HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
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
                    'status' => HttpResponse::HTTP_BAD_REQUEST,
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
                    'status' => HttpResponse::HTTP_NOT_FOUND,
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
                    'status' => HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
                ],
            ],
        ]), );

        // Overriding User endpoints
        $this->endpointFactory = new ResendEmailEndpointFactory();
        $this->endpointFactory->createEndpoint($openApi);

        $UuidWithExamplePathParam = new Model\Parameter(
            name: 'id',
            in: 'path',
            description: 'User identifier',
            required: true,
            example: '2b10b7a3-67f0-40ea-a367-44263321592a'
        );

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
                    'status' => HttpResponse::HTTP_CONFLICT,
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'An error occurred',
                    'detail' => 'user@example.com address is already registered. Please use a different email address or try logging in.',
                ],
            ],
        ]), );

        $openApi->getPaths()->addPath('/api/users', $pathItem->withPost(
            $operationPost
                ->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)
                ->withResponse(HttpResponse::HTTP_CONFLICT, $duplicateEmailResponse)
                ->withResponse(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $standardResponse422)
        )
            ->withGet($operationGet->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)));

        $pathItem = $openApi->getPaths()->getPath('/api/users/{id}');
        $operationPut = $pathItem->getPut();
        $operationPatch = $pathItem->getPatch();
        $operationDelete = $pathItem->getDelete();
        $operationGet = $pathItem->getGet();

        $openApi->getPaths()->addPath('/api/users/{id}', $pathItem->withPut(
            $operationPut->withParameters([$UuidWithExamplePathParam])
                ->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)
                ->withResponse(HttpResponse::HTTP_NOT_FOUND, $standardResponse404)
                ->withResponse(HttpResponse::HTTP_CONFLICT, $duplicateEmailResponse)
                ->withResponse(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $standardResponse422)
        )->withPatch(
            $operationPatch->withParameters([$UuidWithExamplePathParam])
                ->withResponse(HttpResponse::HTTP_BAD_REQUEST, $standardResponse400)
                ->withResponse(HttpResponse::HTTP_NOT_FOUND, $standardResponse404)
                ->withResponse(HttpResponse::HTTP_CONFLICT, $duplicateEmailResponse)
                ->withResponse(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $standardResponse422)
        )->withDelete(
            $operationDelete->withParameters([$UuidWithExamplePathParam])
                ->withResponses([
                    HttpResponse::HTTP_NO_CONTENT => new Response(
                        description: 'User resource deleted',
                        content: new \ArrayObject([
                        'application/json' => [
                            'example' => '',
                        ],
                    ]),
                    ),
                    HttpResponse::HTTP_NOT_FOUND => $standardResponse404])
        )->withGet($operationGet->withParameters([$UuidWithExamplePathParam])
            ->withResponse(HttpResponse::HTTP_NOT_FOUND, $standardResponse404)));

        // Customising confirm endpoint
        $this->endpointFactory = new ConfirmUserEndpointFactory();
        $this->endpointFactory->createEndpoint($openApi);

        // Adding OAuthDocs
        $this->endpointFactory = new OAuthAuthorizeEndpointFactory();
        $this->endpointFactory->createEndpoint($openApi);

        $this->endpointFactory = new OAuthTokenEndpointFactory();
        $this->endpointFactory->createEndpoint($openApi);

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

        return $openApi->withServers([new Model\Server('https://localhost')]);
    }
}
