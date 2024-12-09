<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\OpenApiFactory;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OpenApiFactoryTest extends UnitTestCase
{
    private InternalErrorFactory $serverErrorResponseFactory;
    private OpenApiFactoryInterface $decoratedFactory;
    private AbstractEndpointFactory $endpointFactory1;
    private AbstractEndpointFactory $endpointFactory2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverErrorResponseFactory =
            $this->createMock(InternalErrorFactory::class);
        $this->decoratedFactory =
            $this->createMock(OpenApiFactoryInterface::class);
        $this->endpointFactory1 =
            $this->createMock(AbstractEndpointFactory::class);
        $this->endpointFactory2 =
            $this->createMock(AbstractEndpointFactory::class);
    }

    public function testInvoke(): void
    {
        $expectedOpenApi = $this->createExpectedOpenApi();
        $this->testInvokeSetExpectations($expectedOpenApi);

        $openApiFactory = new OpenApiFactory(
            $this->decoratedFactory,
            [$this->endpointFactory1, $this->endpointFactory2],
            $this->serverErrorResponseFactory
        );

        $result = $openApiFactory->__invoke([]);
        $this->assertEquals($expectedOpenApi, $result);
    }

    public function testAddServerErrorResponseToPath(): void
    {
        $paths = $this->createMock(Paths::class);
        $openApi = new OpenApi(
            $this->createMock(Info::class),
            [],
            $paths
        );

        $url = $this->faker->url();
        $pathItem = new PathItem();
        $paths->method('getPath')
            ->with($url)
            ->willReturn($pathItem);

        $openApiFactory = new OpenApiFactory(
            $this->createMock(OpenApiFactoryInterface::class),
            [],
            $this->serverErrorResponseFactory
        );

        $this->getReflectionMethod('addServerErrorResponseToPath')
            ->invokeArgs($openApiFactory, [$openApi, $url, new Response()]);

        $addedPathItem = $openApi->getPaths()->getPath($url);
        $this->assertEquals($pathItem, $addedPathItem);
    }

    public function testAddServerErrorResponseToAllEndpoints(): void
    {
        $paths = $this->createMock(Paths::class);
        $openApi = $this->createMock(OpenApi::class);

        $openApi->method('getPaths')->willReturn($paths);

        $url = $this->faker->url();
        $pathItem = new PathItem();
        $paths->method('getPaths')->willReturn([$url => $url]);

        $paths->method('getPath')->willReturn($pathItem);

        $serverErrorResponse = new Response();

        $this->serverErrorResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($serverErrorResponse);

        $openApiFactory = new OpenApiFactory(
            $this->createMock(OpenApiFactoryInterface::class),
            [],
            $this->serverErrorResponseFactory
        );

        $this->getReflectionMethod('addServerErrorResponseToAllEndpoints')
            ->invokeArgs($openApiFactory, [$openApi]);

        $this->testAddErrorResponseMakeAssertions($paths, $serverErrorResponse);
    }

    private function createExpectedOpenApi(): OpenApi
    {
        $components = $this->createComponents();
        $expectedOpenApi = new OpenApi(
            $this->createMock(Info::class),
            [],
            $this->createMock(Paths::class)
        );

        return $expectedOpenApi
            ->withComponents($components)
            ->withServers([new Server('https://api.vilnacrm.com')])
            ->withSecurity([
                ['ApiKeyAuth' => []],
                ['BasicAuth' => []],
                ['BearerAuth' => []],
                ['OAuth2' => []],
            ]);
    }

    private function createComponents(): Components
    {
        return (new Components())->
        withSecuritySchemes($this->createSecuritySchemes());
    }

    private function createSecuritySchemes(): ArrayObject
    {
        return new ArrayObject([
            'ApiKeyAuth' => $this->createApiKeyAuthScheme(),
            'BasicAuth' => $this->createBasicAuthScheme(),
            'BearerAuth' => $this->createBearerAuthScheme(),
            'OAuth2' => $this->createOAuth2Scheme(),
        ]);
    }

    /**
     * @return array<string>
     */
    private function createApiKeyAuthScheme(): array
    {
        return [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-KEY',
        ];
    }

    /**
     * @return array<string>
     */
    private function createBasicAuthScheme(): array
    {
        return [
            'type' => 'http',
            'scheme' => 'basic',
        ];
    }

    /**
     * @return array<string>
     */
    private function createBearerAuthScheme(): array
    {
        return [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
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

    private function getReflectionMethod(string $method): \ReflectionMethod
    {
        return new \ReflectionMethod(
            OpenApiFactory::class,
            $method
        );
    }

    private function testAddErrorResponseMakeAssertions(
        Paths $paths,
        Response $serverErrorResponse
    ): void {
        foreach ($paths as $pathItem) {
            $this->processPathItem($pathItem, $serverErrorResponse);
        }
    }

    private function processPathItem(
        PathItem $pathItem,
        Response $serverErrorResponse
    ): void {
        $operations = [
            $pathItem->getGet(),
            $pathItem->getPost(),
            $pathItem->getPut(),
            $pathItem->getPatch(),
            $pathItem->getDelete(),
        ];
        foreach ($operations as $operation) {
            if ($operation instanceof Operation) {
                $this->processOperation($operation, $serverErrorResponse);
            }
        }
    }

    private function processOperation(
        Operation $operation,
        Response $serverErrorResponse
    ): void {
        $responses = $operation->getResponses();
        $this->assertArrayHasKey(
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
            $responses
        );
        $this->assertEquals(
            $serverErrorResponse,
            $responses[HttpResponse::HTTP_INTERNAL_SERVER_ERROR]
        );
    }

    private function testInvokeSetExpectations(
        OpenApi $expectedOpenApi
    ): void {
        $this->decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->willReturn($expectedOpenApi);

        $this->endpointFactory1->expects($this->once())
            ->method('createEndpoint')
            ->with($expectedOpenApi);
        $this->endpointFactory2->expects($this->once())
            ->method('createEndpoint')
            ->with($expectedOpenApi);

        $serverErrorResponse = new Response();
        $this->serverErrorResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($serverErrorResponse);
    }
}
