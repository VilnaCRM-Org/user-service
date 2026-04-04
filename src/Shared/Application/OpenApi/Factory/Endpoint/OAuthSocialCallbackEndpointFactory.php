<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthSocialCallbackEndpointFactory implements EndpointFactoryInterface
{
    private const AUTH_COOKIE_EXAMPLE = <<<'COOKIE'
__Host-auth_token=eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9; Path=/; Secure; HttpOnly; SameSite=Lax
COOKIE;
    private const SUPPORTED_PROVIDERS = [
        'github',
        'google',
        'facebook',
        'twitter',
    ];

    private string $endpointUri = '/auth/social/{provider}/callback';

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

    private function createCodeParameter(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'code',
            in: 'query',
            description: 'Authorization code received from the provider.',
            required: true,
            schema: ['type' => 'string'],
            example: 'provider-code-123',
        );
    }

    private function createStateParameter(): Model\Parameter
    {
        return new Model\Parameter(
            name: 'state',
            in: 'query',
            description: 'OAuth state value received from the provider.',
            required: true,
            schema: ['type' => 'string'],
            example: 'state-123',
        );
    }

    /**
     * @return array<int, Model\Response>
     *
     * @psalm-return array{200: Model\Response, 400: Model\Response, 422: Model\Response, 503: Model\Response}
     */
    private function createResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->createSuccessResponse(),
            HttpResponse::HTTP_BAD_REQUEST => $this->createProblemResponse(
                HttpResponse::HTTP_BAD_REQUEST,
                'Provider mismatch: expected github, got google',
                'provider_mismatch',
            ),
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->createProblemResponse(
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
                'Invalid or already consumed OAuth state',
                'invalid_state',
            ),
            HttpResponse::HTTP_SERVICE_UNAVAILABLE => $this->createProblemResponse(
                HttpResponse::HTTP_SERVICE_UNAVAILABLE,
                'OAuth provider github error: Mock provider token exchange failed.',
                'provider_unavailable',
            ),
        ];
    }

    private function createSuccessResponse(): Model\Response
    {
        return new Model\Response(
            description: 'Direct sign-in or pending 2FA response.',
            content: $this->createSuccessContent(),
            headers: $this->createSuccessHeaders(),
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
            summary: 'Handle social OAuth callback',
            description: 'Completes the social OAuth sign-in flow.',
            get: $this->createOperation(),
        );
    }

    private function createOperation(): Model\Operation
    {
        return new Model\Operation(
            operationId: 'oauth_social_callback_get',
            summary: 'Handle social OAuth callback',
            description: $this->createOperationDescription(),
            tags: ['OAuth'],
            parameters: $this->createParameters(),
            responses: $this->createResponses(),
            security: [],
        );
    }

    private function createOperationDescription(): string
    {
        return implode(
            ' ',
            [
                'Consumes the OAuth state, exchanges the provider code,',
                'and returns either tokens or a pending 2FA session.',
            ],
        );
    }

    /**
     * @return array<int, Model\Parameter>
     */
    private function createParameters(): array
    {
        return [
            $this->createProviderParameter(),
            $this->createCodeParameter(),
            $this->createStateParameter(),
        ];
    }

    private function createSuccessContent(): ArrayObject
    {
        return new ArrayObject([
            'application/json' => new Model\MediaType(
                schema: $this->createSuccessSchema(),
                example: $this->createSuccessExample(),
            ),
        ]);
    }

    private function createSuccessSchema(): ArrayObject
    {
        return new ArrayObject([
            'type' => 'object',
            'properties' => [
                '2fa_enabled' => ['type' => 'boolean'],
                'access_token' => ['type' => 'string'],
                'refresh_token' => ['type' => 'string'],
                'pending_session_id' => ['type' => 'string'],
            ],
            'required' => ['2fa_enabled'],
        ]);
    }

    /**
     * @return array<string, bool|string>
     */
    private function createSuccessExample(): array
    {
        return [
            '2fa_enabled' => false,
            'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9',
            'refresh_token' => 'refresh-token-123',
        ];
    }

    private function createSuccessHeaders(): ArrayObject
    {
        return new ArrayObject([
            'Set-Cookie' => $this->createAuthCookieHeader(),
            'Cache-Control' => $this->createCacheControlHeader(),
            'Pragma' => $this->createPragmaHeader(),
        ]);
    }

    /**
     * @return array{
     *     description: string,
     *     schema: array{type: string},
     *     example: string
     * }
     */
    private function createAuthCookieHeader(): array
    {
        return [
            'description' => 'Issued when 2FA is not required.',
            'schema' => ['type' => 'string'],
            'example' => self::AUTH_COOKIE_EXAMPLE,
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

    /**
     * @return array{
     *     description: string,
     *     schema: array{type: string},
     *     example: string
     * }
     */
    private function createPragmaHeader(): array
    {
        return [
            'description' => 'Legacy cache control policy.',
            'schema' => ['type' => 'string'],
            'example' => 'no-cache',
        ];
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
