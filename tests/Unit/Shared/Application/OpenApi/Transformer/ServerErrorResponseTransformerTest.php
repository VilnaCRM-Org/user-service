<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Response\AbstractResponseFactory;
use App\Shared\Application\OpenApi\Transformer\ServerErrorResponseTransformer;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ServerErrorResponseTransformerTest extends UnitTestCase
{
    public function testTransformAddsInternalServerErrorResponse(): void
    {
        $internalErrorResponse = new Response(description: 'Server error');
        $internalErrorFactory = $this->createInternalErrorFactory($internalErrorResponse);
        $openApi = $this->createOpenApiWithOperations();
        $transformer = new ServerErrorResponseTransformer($internalErrorFactory);
        $transformer->transform($openApi);
        $this->assertBothOperationsHaveServerError($openApi, $internalErrorResponse);
    }

    private function createInternalErrorFactory(
        Response $response
    ): MockObject&AbstractResponseFactory {
        $factory = $this->createMock(AbstractResponseFactory::class);
        $factory->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        return $factory;
    }

    private function createOpenApiWithOperations(): OpenApi
    {
        $operation = new Operation(responses: []);
        $pathItem = (new PathItem())
            ->withGet($operation)
            ->withPost($operation);

        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);

        return new OpenApi(new Info('Test', '1.0.0'), [], $paths);
    }

    private function assertBothOperationsHaveServerError(
        OpenApi $openApi,
        Response $internalErrorResponse
    ): void {
        $path = $openApi->getPaths()->getPath('/resource');

        $this->assertResponseContainsServerError($path->getGet(), $internalErrorResponse);
        $this->assertResponseContainsServerError($path->getPost(), $internalErrorResponse);
    }

    private function assertResponseContainsServerError(
        ?Operation $operation,
        Response $expectedResponse
    ): void {
        $responses = $operation?->getResponses() ?? [];

        $this->assertArrayHasKey(HttpResponse::HTTP_INTERNAL_SERVER_ERROR, $responses);
        $this->assertSame(
            $expectedResponse,
            $responses[HttpResponse::HTTP_INTERNAL_SERVER_ERROR]
        );
    }
}
