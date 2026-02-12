<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Augmenter\ServerErrorResponseAugmenter;
use App\Shared\Application\OpenApi\Cleaner\NoContentResponseCleaner;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\Sanitizer\PaginationQueryParametersSanitizer;
use App\Shared\Application\OpenApi\Sanitizer\PathParametersSanitizer;
use ArrayObject;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    private const OAUTH2_DESCRIPTION
        = 'OAuth2 Authorization Code flow securing VilnaCRM API.';
    private const OAUTH_CLIENT_BASIC_DESCRIPTION
        = 'HTTP Basic authentication for OAuth client credentials.';

    /**
     * @param iterable<EndpointFactoryInterface> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $endpointFactories,
        private string $serverUrl,
        private PathParametersSanitizer $pathParametersSanitizer,
        private ServerErrorResponseAugmenter $serverErrorResponseAugmenter,
        private PaginationQueryParametersSanitizer $paginationSanitizer,
        private NoContentResponseCleaner $noContentResponseCleaner
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    #[\Override]
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $openApi = $openApi
            ->withComponents($this->augmentComponents($openApi))
            ->withTags($this->buildTags());

        foreach ($this->endpointFactories as $endpointFactory) {
            $endpointFactory->createEndpoint($openApi);
        }

        $this->serverErrorResponseAugmenter->augment($openApi);
        $openApi = $this->pathParametersSanitizer->sanitize($openApi);
        $openApi = $this->paginationSanitizer->sanitize($openApi);
        $openApi = $this->noContentResponseCleaner->clean($openApi);

        return $openApi->withServers([
            new Model\Server($this->serverUrl),
        ])->withSecurity([
            ['OAuth2' => []],
        ]);
    }

    private function augmentComponents(OpenApi $openApi): Components
    {
        $components = $openApi->getComponents() ?? new Components();
        $securitySchemes = $components->getSecuritySchemes()
            ?? new ArrayObject();
        $securitySchemes['OAuth2'] = $this->createOAuth2Scheme();
        $securitySchemes['OAuthClientBasic'] = [
            'type' => 'http',
            'scheme' => 'basic',
            'description' => self::OAUTH_CLIENT_BASIC_DESCRIPTION,
        ];

        return $components->withSecuritySchemes($securitySchemes);
    }

    /**
     * @return Tag[]
     *
     * @psalm-return list{Tag, Tag, Tag, Tag, Tag}
     */
    private function buildTags(): array
    {
        return [
            new Tag('Authentication', 'Authentication and two-factor endpoints'),
            new Tag('HealthCheck', 'Service health monitoring endpoints'),
            new Tag('OAuth', 'OAuth 2.0 authorization and token endpoints'),
            new Tag('User', 'User account management operations'),
            new Tag('User reset password', 'Password reset workflows'),
        ];
    }

    /**
     * @return array<array<array<string|array<string>>>|string>
     *
     * @psalm-return array{type: 'oauth2', description: 'OAuth2 Authorization Code flow securing VilnaCRM API.', flows: array{authorizationCode: array{authorizationUrl: 'https://localhost/api/oauth/dialog', tokenUrl: 'https://localhost/api/oauth/token', scopes: array{'write:pets': 'modify pets in your account', 'read:pets': 'read your pets'}}}}
     */
    private function createOAuth2Scheme(): array
    {
        return [
            'type' => 'oauth2',
            'description' => self::OAUTH2_DESCRIPTION,
            'flows' => [
                'authorizationCode' => $this->createOAuth2CodeFlow(),
            ],
        ];
    }

    /**
     * @return array<string|array<string>>
     *
     * @psalm-return array{authorizationUrl: 'https://localhost/api/oauth/dialog', tokenUrl: 'https://localhost/api/oauth/token', scopes: array{'write:pets': 'modify pets in your account', 'read:pets': 'read your pets'}}
     */
    private function createOAuth2CodeFlow(): array
    {
        return [
            'authorizationUrl' => 'https://localhost/api/oauth/dialog',
            'tokenUrl' => 'https://localhost/api/oauth/token',
            'scopes' => [
                'write:pets' => 'modify pets in your account',
                'read:pets' => 'read your pets',
            ],
        ];
    }
}
