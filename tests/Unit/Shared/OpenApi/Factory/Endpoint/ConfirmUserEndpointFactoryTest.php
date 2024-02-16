<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\OpenApi\Factory\Endpoint\ConfirmUserEndpointFactory;
use App\Shared\OpenApi\Factory\Response\TokenNotFoundResponseFactory;
use App\Shared\OpenApi\Factory\Response\UserConfirmedResponseFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ConfirmUserEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $userConfirmedResponse = new Response();
        $notFoundResponse = new Response();

        $tokenNotFoundResponseFactory = $this->createMock(TokenNotFoundResponseFactory::class);
        $tokenNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($notFoundResponse);

        $userConfirmedResponseFactory = $this->createMock(UserConfirmedResponseFactory::class);
        $userConfirmedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($userConfirmedResponse);

        $factory = new ConfirmUserEndpointFactory($tokenNotFoundResponseFactory, $userConfirmedResponseFactory);

        $openApi = $this->createMock(OpenApi::class);
        $paths = $this->createMock(Paths::class);
        $pathItem = new PathItem();
        $patchOperation = $this->createMock(Operation::class);
        $patchOperation->method('getResponses')->willReturn([
            HttpResponse::HTTP_OK => $userConfirmedResponse,
            HttpResponse::HTTP_NOT_FOUND => $notFoundResponse,
        ]);
        $pathItem = $pathItem->withPatch($patchOperation);
        $paths->method('getPath')->willReturn($pathItem);
        $openApi->method('getPaths')->willReturn($paths);

        $factory->createEndpoint($openApi);

        $patchOperation = $pathItem->getPatch();
        $responses = $patchOperation->getResponses() ?? [];
        $this->assertEquals(
            [
                HttpResponse::HTTP_OK => $userConfirmedResponse,
                HttpResponse::HTTP_NOT_FOUND => $notFoundResponse,
            ],
            $responses
        );
    }
}
