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
use App\Shared\Application\OpenApi\Augmenter\ServerErrorResponseAugmenter;
use App\Shared\Application\OpenApi\Cleaner\NoContentResponseCleaner;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\OpenApiFactory;
use App\Shared\Application\OpenApi\Sanitizer\PaginationQueryParametersSanitizer;
use App\Shared\Application\OpenApi\Sanitizer\PathParametersSanitizer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use PHPUnit\Framework\MockObject\MockObject;

final class OpenApiFactoryTest extends UnitTestCase
{
    private MockObject $decoratedFactory;
    private MockObject $endpointFactoryOne;
    private MockObject $endpointFactoryTwo;
    private MockObject $pathParametersSanitizer;
    private MockObject $errorResponseAugmenter;
    private MockObject $paginationQueryParametersSanitizer;
    private MockObject $noContentResponseCleaner;

    #[\Override]
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
        $this->paginationQueryParametersSanitizer =
            $this->createMock(PaginationQueryParametersSanitizer::class);
        $this->noContentResponseCleaner =
            $this->createMock(NoContentResponseCleaner::class);
    }

    public function testInvokeDecoratesOpenApiDocument(): void
    {
        $initialDocument = $this->createBaseOpenApi();
        $this->setupDecoratorExpectations($initialDocument);
        $factory = $this->createFactory();

        $result = $factory->__invoke([]);

        $this->assertEquals($this->createExpectedOpenApi($initialDocument), $result);
    }

    public function testInvokeAddsSecuritySchemeWhenComponentsMissing(): void
    {
        $emptyDocument = $this->createBaseOpenApi();
        $this->setupFactoryExpectation($emptyDocument);
        $this->setupProcessorExpectations();
        $factory = $this->createFactory([]);

        $result = $factory->__invoke([]);

        $this->assertSecuritySchemesPresent($result);
    }

    private function setupDecoratorExpectations(OpenApi $initialDocument): void
    {
        $this->setupFactoryExpectation($initialDocument);
        $this->setupEndpointFactoryExpectations();
        $this->setupProcessorExpectations();
    }

    private function setupFactoryExpectation(OpenApi $initialDocument): void
    {
        $this->decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->with([])
            ->willReturn($initialDocument);
    }

    private function setupEndpointFactoryExpectations(): void
    {
        $this->endpointFactoryOne->expects($this->once())
            ->method('createEndpoint')
            ->with($this->isInstanceOf(OpenApi::class));
        $this->endpointFactoryTwo->expects($this->once())
            ->method('createEndpoint')
            ->with($this->isInstanceOf(OpenApi::class));
    }

    private function setupProcessorExpectations(): void
    {
        $this->errorResponseAugmenter->expects($this->once())
            ->method('augment')
            ->with($this->isInstanceOf(OpenApi::class));

        $this->pathParametersSanitizer->expects($this->once())
            ->method('sanitize')
            ->with($this->isInstanceOf(OpenApi::class))
            ->willReturnCallback(static fn (OpenApi $document) => $document);

        $this->paginationQueryParametersSanitizer->expects($this->once())
            ->method('sanitize')
            ->with($this->isInstanceOf(OpenApi::class))
            ->willReturnCallback(static fn (OpenApi $document) => $document);

        $this->noContentResponseCleaner->expects($this->once())
            ->method('clean')
            ->with($this->isInstanceOf(OpenApi::class))
            ->willReturnCallback(static fn (OpenApi $document) => $document);
    }

    private function assertSecuritySchemesPresent(OpenApi $result): void
    {
        $components = $result->getComponents();
        $this->assertNotNull($components);
        $schemes = $components->getSecuritySchemes();
        $this->assertArrayHasKey('OAuth2', $schemes);
        $this->assertArrayHasKey('OAuthClientBasic', $schemes);
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
            $this->errorResponseAugmenter,
            $this->paginationQueryParametersSanitizer,
            $this->noContentResponseCleaner
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
        $securitySchemes['OAuthClientBasic'] = [
            'type' => 'http',
            'scheme' => 'basic',
            'description' => 'HTTP Basic authentication for OAuth client credentials.',
        ];

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
     *     description: string,
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
            'description' => 'OAuth2 Authorization Code flow securing VilnaCRM API.',
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
