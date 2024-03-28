<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\ConfirmUserEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Response\TokenNotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserConfirmedFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ConfirmUserEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $userConfirmedResponse = new Response();
        $notFoundResponse = new Response();

        $tokenNotFoundResponseFactory = $this->createMock(TokenNotFoundFactory::class);
        $tokenNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($notFoundResponse);

        $userConfirmedResponseFactory = $this->createMock(UserConfirmedFactory::class);
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
