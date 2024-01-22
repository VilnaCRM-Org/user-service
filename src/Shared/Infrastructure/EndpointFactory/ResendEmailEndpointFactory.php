<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EndpointFactory;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ResendEmailEndpointFactory implements AbstractEndpointFactory
{
    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath('/api/users/{id}/resend-confirmation-email');
        $operation = $pathItem->getPost();

        $UuidWithExamplePathParam = new Model\Parameter(
            name: 'id',
            in: 'path',
            description: 'User identifier',
            required: true,
            example: '2b10b7a3-67f0-40ea-a367-44263321592a'
        );

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
        ]),);

        $openApi->getPaths()->addPath(
            '/api/users/{id}/resend-confirmation-email',
            $pathItem->withPost(
                $operation
                    ->withParameters([$UuidWithExamplePathParam])
                    ->withDescription('Resends confirmation email')
                    ->withSummary('Resends confirmation email')
                    ->withRequestBody(new Model\RequestBody(content: new \ArrayObject([
                        'application/json' => [
                            'example' => '{}',
                        ],])))
                    ->withResponses([
                        HttpResponse::HTTP_OK => new Response(
                            description: 'Email was send again',
                            content: new \ArrayObject([
                                'application/json' => [
                                    'example' => '',
                                ],
                            ]),),
                        HttpResponse::HTTP_NOT_FOUND => $standardResponse404,
                        HttpResponse::HTTP_TOO_MANY_REQUESTS => new Response(
                            description: 'Too many requests',
                            content: new \ArrayObject([
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
                                        'status' => HttpResponse::HTTP_TOO_MANY_REQUESTS,
                                    ],
                                ],
                            ]),),
                    ])
            ));
    }
}