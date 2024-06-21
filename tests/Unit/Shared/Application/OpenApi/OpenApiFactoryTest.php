<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
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
        $expectedOpenApi = new OpenApi(
            $this->createMock(Info::class),
            [],
            $this->createMock(Paths::class)
        );
        $expectedOpenApi =
            $expectedOpenApi->withServers([new Server('https://localhost')]);

        $this->testInvokeSetExpectations(
            $expectedOpenApi
        );

        $openApiFactory = new OpenApiFactory(
            $this->decoratedFactory,
            [$this->endpointFactory1, $this->endpointFactory2],
            $this->serverErrorResponseFactory
        );

        $result = $openApiFactory->__invoke();

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
