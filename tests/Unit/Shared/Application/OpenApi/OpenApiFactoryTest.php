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

class OpenApiFactoryTest extends UnitTestCase
{
    public function testInvoke(): void
    {
        $decoratedFactory = $this->createMock(OpenApiFactoryInterface::class);
        $endpointFactory1 = $this->createMock(AbstractEndpointFactory::class);
        $endpointFactory2 = $this->createMock(AbstractEndpointFactory::class);
        $serverErrorResponseFactory = $this->createMock(InternalErrorFactory::class);

        $expectedOpenApi = new OpenApi(
            $this->createMock(Info::class),
            [],
            $this->createMock(Paths::class)
        );
        $expectedOpenApi = $expectedOpenApi->withServers([new Server('https://localhost')]);

        $decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->willReturn($expectedOpenApi);

        $endpointFactory1->expects($this->once())
            ->method('createEndpoint')
            ->with($expectedOpenApi);
        $endpointFactory2->expects($this->once())
            ->method('createEndpoint')
            ->with($expectedOpenApi);

        $serverErrorResponse = new Response();
        $serverErrorResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($serverErrorResponse);

        $openApiFactory = new OpenApiFactory(
            $decoratedFactory,
            [$endpointFactory1, $endpointFactory2],
            $serverErrorResponseFactory
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

        $serverErrorResponse = new Response();

        $serverErrorResponseFactory = $this->createMock(InternalErrorFactory::class);

        $openApiFactory = new OpenApiFactory(
            $this->createMock(OpenApiFactoryInterface::class),
            [],
            $serverErrorResponseFactory
        );

        $reflectionMethod = new \ReflectionMethod(OpenApiFactory::class, 'addServerErrorResponseToPath');
        $reflectionMethod->invokeArgs($openApiFactory, [$openApi, $url, $serverErrorResponse]);

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
        $paths->method('getPaths')
            ->willReturn([$url => $url]);

        $paths->method('getPath')
            ->willReturn($pathItem);

        $serverErrorResponse = new Response();

        $serverErrorResponseFactory = $this->createMock(InternalErrorFactory::class);
        $serverErrorResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($serverErrorResponse);

        $openApiFactory = new OpenApiFactory(
            $this->createMock(OpenApiFactoryInterface::class),
            [],
            $serverErrorResponseFactory
        );

        $reflectionMethod = new \ReflectionMethod(OpenApiFactory::class, 'addServerErrorResponseToAllEndpoints');
        $reflectionMethod->invokeArgs($openApiFactory, [$openApi]);

        foreach ($paths as $pathItem) {
            $operations = [
                $pathItem->getGet(),
                $pathItem->getPost(),
                $pathItem->getPut(),
                $pathItem->getPatch(),
                $pathItem->getDelete(),
            ];
            foreach ($operations as $operation) {
                if ($operation instanceof Operation) {
                    $responses = $operation->getResponses();
                    $this->assertArrayHasKey(HttpResponse::HTTP_INTERNAL_SERVER_ERROR, $responses);
                    $this->assertEquals($serverErrorResponse, $responses[HttpResponse::HTTP_INTERNAL_SERVER_ERROR]);
                }
            }
        }
    }

}
