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
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\TokenNotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserConfirmedFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ConfirmUserEndpointFactoryTest extends UnitTestCase
{
    private Response $userConfirmedResponse;
    private Response $notFoundResponse;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private PathItem $pathItem;
    private Operation $patchOperation;
    private TokenNotFoundFactory $tokenNotFoundResponseFactory;
    private UserConfirmedFactory $userConfirmedResponseFactory;
    private ValidationErrorFactory $validationErrorFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private ConfirmUserRequestFactory $confirmUserRequestFactory;
    private OpenApi $openApi;
    private Paths $paths;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userConfirmedResponse = new Response();
        $this->notFoundResponse = new Response();
        $this->validationErrorResponse = new Response();
        $this->badRequestResponse = new Response();
        $this->patchOperation = $this->createMock(Operation::class);

        $this->pathItem = new PathItem();
        $this->pathItem = $this->pathItem->withPatch($this->patchOperation);

        $this->tokenNotFoundResponseFactory =
            $this->createMock(TokenNotFoundFactory::class);
        $this->userConfirmedResponseFactory =
            $this->createMock(UserConfirmedFactory::class);
        $this->validationErrorFactory =
            $this->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory =
            $this->createMock(BadRequestResponseFactory::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->confirmUserRequestFactory =
            $this->createMock(ConfirmUserRequestFactory::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = $this->createConfirmUserEndpointFactory();

        $factory->createEndpoint($this->openApi);
        $this->patchOperation = $this->pathItem->getPatch();
        $this->assertExpectedResponses();
    }

    private function createConfirmUserEndpointFactory(): ConfirmUserEndpointFactory
    {
        return new ConfirmUserEndpointFactory(
            getenv('API_PREFIX'),
            $this->tokenNotFoundResponseFactory,
            $this->badRequestResponseFactory,
            $this->userConfirmedResponseFactory,
            $this->validationErrorFactory,
            $this->confirmUserRequestFactory
        );
    }

    private function assertExpectedResponses(): void
    {
        $responses = $this->patchOperation->getResponses() ?? [];
        $this->assertEquals([
            HttpResponse::HTTP_OK => $this->userConfirmedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ], $responses);
    }

    private function setExpectations(): void
    {
        $this->tokenNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->notFoundResponse);
        $this->badRequestResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->badRequestResponse);
        $this->userConfirmedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userConfirmedResponse);
        $this->validationErrorFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->validationErrorResponse);
        $this->patchOperation->method('getResponses')->willReturn([
            HttpResponse::HTTP_OK => $this->userConfirmedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ]);
        $this->paths->method('getPath')->willReturn($this->pathItem);
        $this->openApi->method('getPaths')->willReturn($this->paths);
    }
}
