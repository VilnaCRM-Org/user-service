<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\ConfirmUserEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\TokenNotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserConfirmedFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ConfirmUserEndpointFactoryTest extends UnitTestCase
{
    private Response $userConfirmedResponse;
    private Response $notFoundResponse;
    private PathItem $pathItem;
    private Operation $patchOperation;
    private TokenNotFoundFactory $tokenNotFoundResponseFactory;
    private UserConfirmedFactory $userConfirmedResponseFactory;
    private ConfirmUserRequestFactory $confirmUserRequestFactory;
    private OpenApi $openApi;
    private Paths $paths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userConfirmedResponse = new Response();
        $this->notFoundResponse = new Response();
        $this->patchOperation = $this->createMock(Operation::class);

        $this->pathItem = new PathItem();
        $this->pathItem = $this->pathItem->withPatch($this->patchOperation);

        $this->tokenNotFoundResponseFactory =
            $this->createMock(TokenNotFoundFactory::class);
        $this->userConfirmedResponseFactory =
            $this->createMock(UserConfirmedFactory::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->confirmUserRequestFactory =
            $this->createMock(ConfirmUserRequestFactory::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new ConfirmUserEndpointFactory(
            $this->tokenNotFoundResponseFactory,
            $this->userConfirmedResponseFactory,
            $this->confirmUserRequestFactory
        );

        $factory->createEndpoint($this->openApi);
        $this->patchOperation = $this->pathItem->getPatch();
        $responses = $this->patchOperation->getResponses() ?? [];
        $this->assertEquals(
            [
                HttpResponse::HTTP_OK => $this->userConfirmedResponse,
                HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
            ],
            $responses
        );
    }

    private function setExpectations(): void
    {
        $this->tokenNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->notFoundResponse);
        $this->userConfirmedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userConfirmedResponse);
        $this->patchOperation->method('getResponses')->willReturn([
            HttpResponse::HTTP_OK => $this->userConfirmedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
        ]);
        $this->paths->method('getPath')->willReturn($this->pathItem);
        $this->openApi->method('getPaths')->willReturn($this->paths);
    }
}
