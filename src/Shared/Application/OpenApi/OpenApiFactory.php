<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Processor\PathParametersSanitizer;
use App\Shared\Application\OpenApi\Processor\ServerErrorResponseAugmenter;
use ArrayObject;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /**
     * @param iterable<AbstractEndpointFactory> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $endpointFactories,
        private string $serverUrl,
        private PathParametersSanitizer $pathParametersSanitizer,
        private ServerErrorResponseAugmenter $serverErrorResponseAugmenter
    ) {
    }

    /**
     * @param array<string, string> $context
     */
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

        return $components->withSecuritySchemes($securitySchemes);
    }

    /**
     * @return array<int, Tag>
     */
    private function buildTags(): array
    {
        return [
            new Tag('HealthCheck', 'Service health monitoring endpoints'),
            new Tag('OAuth', 'OAuth 2.0 authorization and token endpoints'),
            new Tag('User', 'User account management operations'),
            new Tag('User reset password', 'Password reset workflows'),
        ];
    }

    /**
     * @return array<string>
     */
    private function createOAuth2Scheme(): array
    {
        return [
            'type' => 'oauth2',
            'flows' => [
                'authorizationCode' => $this->createOAuth2CodeFlow(),
            ],
        ];
    }

    /**
     * @return array<string>
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
