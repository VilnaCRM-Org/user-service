<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Processor\ServerErrorResponseAugmenter;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ServerErrorResponseAugmenterTest extends UnitTestCase
{
    public function testAugmentAddsInternalServerErrorResponse(): void
    {
        $internalErrorResponse = new Response(description: 'Server error');

        $internalErrorFactory = $this->createMock(InternalErrorFactory::class);
        $internalErrorFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($internalErrorResponse);

        $operation = new Operation(responses: []);
        $pathItem = (new PathItem())
            ->withGet($operation)
            ->withPost($operation);

        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $augmenter = new ServerErrorResponseAugmenter($internalErrorFactory);
        $augmenter->augment($openApi);

        $path = $openApi->getPaths()->getPath('/resource');

        $this->assertResponseContainsServerError(
            $path->getGet(),
            $internalErrorResponse
        );
        $this->assertResponseContainsServerError(
            $path->getPost(),
            $internalErrorResponse
        );
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
