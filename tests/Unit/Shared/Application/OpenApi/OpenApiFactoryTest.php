<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\OpenApiFactory;
use App\Shared\Application\OpenApi\Processor\PathParametersSanitizer;
use App\Shared\Application\OpenApi\Processor\ServerErrorResponseAugmenter;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use PHPUnit\Framework\MockObject\MockObject;

final class OpenApiFactoryTest extends UnitTestCase
{
    private OpenApiFactoryInterface&MockObject $decoratedFactory;
    private AbstractEndpointFactory&MockObject $endpointFactoryOne;
    private AbstractEndpointFactory&MockObject $endpointFactoryTwo;
    private PathParametersSanitizer&MockObject $pathParametersSanitizer;
    private ServerErrorResponseAugmenter&MockObject $errorResponseAugmenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedFactory =
            $this->createMock(OpenApiFactoryInterface::class);
        $this->endpointFactoryOne =
            $this->createMock(AbstractEndpointFactory::class);
        $this->endpointFactoryTwo =
            $this->createMock(AbstractEndpointFactory::class);
        $this->pathParametersSanitizer =
            $this->createMock(PathParametersSanitizer::class);
        $this->errorResponseAugmenter =
            $this->createMock(ServerErrorResponseAugmenter::class);
    }

    public function testInvokeDecoratesOpenApiDocument(): void
    {
        $initialDocument = $this->createBaseOpenApi();

        $this->decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->with([])
            ->willReturn($initialDocument);

        $this->endpointFactoryOne->expects($this->once())
            ->method('createEndpoint')
            ->with($this->isInstanceOf(OpenApi::class));
        $this->endpointFactoryTwo->expects($this->once())
            ->method('createEndpoint')
            ->with($this->isInstanceOf(OpenApi::class));

        $this->errorResponseAugmenter->expects($this->once())
            ->method('augment')
            ->with($this->isInstanceOf(OpenApi::class));

        $this->pathParametersSanitizer->expects($this->once())
            ->method('sanitize')
            ->with($this->isInstanceOf(OpenApi::class))
            ->willReturnCallback(static fn (OpenApi $document) => $document);

        $factory = $this->createFactory();

        $result = $factory->__invoke([]);

        $this->assertEquals(
            $this->createExpectedOpenApi($initialDocument),
            $result
        );
    }

    public function testInvokeAddsSecuritySchemeWhenComponentsMissing(): void
    {
        $emptyDocument = $this->createBaseOpenApi();

        $this->decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->willReturn($emptyDocument);

        $this->errorResponseAugmenter->expects($this->once())
            ->method('augment')
            ->with($this->isInstanceOf(OpenApi::class));

        $this->pathParametersSanitizer->expects($this->once())
            ->method('sanitize')
            ->with($this->isInstanceOf(OpenApi::class))
            ->willReturnCallback(static fn (OpenApi $document) => $document);

        $factory = $this->createFactory([]);

        $result = $factory->__invoke([]);

        $components = $result->getComponents();
        $this->assertNotNull($components);
        $this->assertArrayHasKey('OAuth2', $components->getSecuritySchemes());
    }

    /**
     * @param array<int, AbstractEndpointFactory&MockObject>|null $endpointFactories
     */
    private function createFactory(?array $endpointFactories = null): OpenApiFactory
    {
        return new OpenApiFactory(
            $this->decoratedFactory,
            $endpointFactories ?? [
                $this->endpointFactoryOne,
                $this->endpointFactoryTwo,
            ],
            getenv('API_URL'),
            $this->pathParametersSanitizer,
            $this->errorResponseAugmenter
        );
    }

    private function createBaseOpenApi(): OpenApi
    {
        return new OpenApi(
            $this->createMock(Info::class),
            [],
            new Paths()
        );
    }

    private function createExpectedOpenApi(OpenApi $base): OpenApi
    {
        $components = $base->getComponents() ?? new Components();
        $securitySchemes = $components->getSecuritySchemes() ?? new ArrayObject();
        $securitySchemes['OAuth2'] = $this->createOAuth2Scheme();

        return $base
            ->withComponents($components->withSecuritySchemes($securitySchemes))
            ->withServers([new Server(getenv('API_URL'))])
            ->withTags($this->createTags())
            ->withSecurity([
                ['OAuth2' => []],
            ]);
    }

    /**
     * @return array<int, Tag>
     */
    private function createTags(): array
    {
        return [
            new Tag('HealthCheck', 'Service health monitoring endpoints'),
            new Tag('OAuth', 'OAuth 2.0 authorization and token endpoints'),
            new Tag('User', 'User account management operations'),
            new Tag('User reset password', 'Password reset workflows'),
        ];
    }

    /**
     * @return array{
     *     type: string,
     *     flows: array{
     *         authorizationCode: array{
     *             authorizationUrl: string,
     *             tokenUrl: string,
     *             scopes: array<string, string>
     *         }
     *     }
     * }
     */
    private function createOAuth2Scheme(): array
    {
        return [
            'type' => 'oauth2',
            'flows' => [
                'authorizationCode' => [
                    'authorizationUrl' => 'https://localhost/api/oauth/dialog',
                    'tokenUrl' => 'https://localhost/api/oauth/token',
                    'scopes' => [
                        'write:pets' => 'modify pets in your account',
                        'read:pets' => 'read your pets',
                    ],
                ],
            ],
        ];
    }
}
