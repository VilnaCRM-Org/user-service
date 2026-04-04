<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthSocialInitiateEndpointFactory implements EndpointFactoryInterface
{
    private const FLOW_COOKIE_EXAMPLE
        = 'oauth_flow_binding=abc123; Path=/api/auth/social; Secure; HttpOnly; SameSite=Lax';
    private const LOCATION_EXAMPLE
        = 'https://oauth.mock.example/github/authorize?state=abc123';
    private const SUPPORTED_PROVIDERS = [
        'github',
        'google',
        'facebook',
        'twitter',
    ];

    private string $endpointUri = '/auth/social/{provider}';

    public function __construct(string $apiPrefix)
    {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
    }

    #[\Override]
    public function createEndpoint(OpenApi $openApi): void
    {
        $openApi->getPaths()->addPath($this->endpointUri, $this->createPathItem());
    }

    private function createProviderParameter(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'provider',
            in: 'path',
            description: 'Supported social OAuth provider.',
            required: true,
            schema: [
                'type' => 'string',
                'enum' => self::SUPPORTED_PROVIDERS,
            ],
            example: 'github',
        );
    }

    /**
     * @return array<int, Model\Response>
     *
     * @psalm-return array{302: Model\Response, 400: Model\Response}
     */
    private function createResponses(): array
    {
        return [
            HttpResponse::HTTP_FOUND => $this->createRedirectResponse(),
            HttpResponse::HTTP_BAD_REQUEST => $this->createProblemResponse(
                HttpResponse::HTTP_BAD_REQUEST,
                'Unsupported OAuth provider "example".',
                'unsupported_provider',
            ),
        ];
    }

    private function createRedirectResponse(): Model\Response
    {
        return new Model\Response(
            description: 'Redirect to the external social provider.',
            headers: $this->createRedirectHeaders(),
            content: $this->createEmptyJsonContent(),
        );
    }

    private function createProblemResponse(
        int $status,
        string $detail,
        string $errorCode,
    ): Model\Response {
        return new Model\Response(
            description: 'RFC 7807 problem response.',
            content: new ArrayObject([
                'application/problem+json' => $this->createProblemMediaType(
                    $status,
                    $detail,
                    $errorCode,
                ),
            ]),
        );
    }

    private function createPathItem(): Model\PathItem
    {
        return new Model\PathItem(
            summary: 'Start social OAuth flow',
            description: 'Redirects to the selected social provider.',
            get: $this->createOperation(),
        );
    }

    private function createOperation(): Model\Operation
    {
        return new Model\Operation(
            operationId: 'oauth_social_initiate_get',
            summary: 'Start social OAuth flow',
            description: $this->createOperationDescription(),
            tags: ['OAuth'],
            parameters: [$this->createProviderParameter()],
            responses: $this->createResponses(),
            security: [],
        );
    }

    private function createOperationDescription(): string
    {
        return implode(
            ' ',
            [
                'Creates an OAuth state record, sets the flow-binding cookie,',
                'and redirects the client to the selected provider.',
            ],
        );
    }

    private function createRedirectHeaders(): ArrayObject
    {
        return new ArrayObject([
            'Location' => $this->createLocationHeader(),
            'Set-Cookie' => $this->createFlowCookieHeader(),
            'Cache-Control' => $this->createCacheControlHeader(),
        ]);
    }

    /**
     * @return array{
     *     description: string,
     *     schema: array{type: string, format: string},
     *     example: string
     * }
     */
    private function createLocationHeader(): array
    {
        return [
            'description' => 'Provider authorization URL.',
            'schema' => [
                'type' => 'string',
                'format' => 'uri',
            ],
            'example' => self::LOCATION_EXAMPLE,
        ];
    }

    /**
     * @return array{
     *     description: string,
     *     schema: array{type: string},
     *     example: string
     * }
     */
    private function createFlowCookieHeader(): array
    {
        return [
            'description' => 'Short-lived flow-binding cookie.',
            'schema' => ['type' => 'string'],
            'example' => self::FLOW_COOKIE_EXAMPLE,
        ];
    }

    /**
     * @return array{
     *     description: string,
     *     schema: array{type: string},
     *     example: string
     * }
     */
    private function createCacheControlHeader(): array
    {
        return [
            'description' => 'Cache control policy.',
            'schema' => ['type' => 'string'],
            'example' => 'no-store',
        ];
    }

    private function createEmptyJsonContent(): ArrayObject
    {
        return new ArrayObject([
            'application/json' => new Model\MediaType(
                example: new ArrayObject(),
            ),
        ]);
    }

    private function createProblemMediaType(
        int $status,
        string $detail,
        string $errorCode,
    ): Model\MediaType {
        return new Model\MediaType(
            schema: $this->createProblemSchema(),
            example: $this->createProblemExample($status, $detail, $errorCode),
        );
    }

    private function createProblemSchema(): ArrayObject
    {
        return new ArrayObject([
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'detail' => ['type' => 'string'],
                'status' => ['type' => 'integer'],
                'error_code' => ['type' => 'string'],
            ],
            'required' => ['type', 'title', 'detail', 'status', 'error_code'],
        ]);
    }

    /**
     * @return array{
     *     type: string,
     *     title: string,
     *     detail: string,
     *     status: int,
     *     error_code: string
     * }
     */
    private function createProblemExample(
        int $status,
        string $detail,
        string $errorCode,
    ): array {
        return [
            'type' => sprintf('/errors/%d', $status),
            'title' => 'An error occurred',
            'detail' => $detail,
            'status' => $status,
            'error_code' => $errorCode,
        ];
    }
}
