<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\ConfirmPasswordResetEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmPasswordResetRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\TokenNotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserUpdatedResponseFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ConfirmPasswordResetEndpointFactoryTest extends UnitTestCase
{
    private Response $userUpdatedResponse;
    private Response $tokenNotFound;
    private Response $userNotFoundResponse;

    private UserUpdatedResponseFactory $userUpdatedResponseFactory;
    private TokenNotFoundFactory $tokenNotFoundFactory;
    private UserNotFoundResponseFactory $userNotFoundResponseFactory;

    private PathItem $pathItem;
    private Operation $postOperation;
    private ConfirmPasswordResetRequestFactory $confirmPasswordResetRequestFactory;

    private OpenApi $openApi;
    private Paths $paths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userUpdatedResponse = new Response();
        $this->tokenNotFound = new Response();
        $this->userNotFoundResponse = new Response();

        $this->userUpdatedResponseFactory = $this->createMock(UserUpdatedResponseFactory::class);
        $this->tokenNotFoundFactory = $this->createMock(TokenNotFoundFactory::class);
        $this->userNotFoundResponseFactory = $this->createMock(UserNotFoundResponseFactory::class);
        $this->confirmPasswordResetRequestFactory = $this->createMock(ConfirmPasswordResetRequestFactory::class);

        $this->postOperation = $this->createMock(Operation::class);

        $this->pathItem = new PathItem();
        $this->pathItem = $this->pathItem->withPatch($this->postOperation);

        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new ConfirmPasswordResetEndpointFactory(
            $this->userUpdatedResponseFactory,
            $this->tokenNotFoundFactory,
            $this->userNotFoundResponseFactory,
            $this->confirmPasswordResetRequestFactory
        );

        $factory->createEndpoint($this->openApi);
        $this->postOperation = $this->pathItem->getPatch();
        $responses = $this->postOperation->getResponses() ?? [];
        $this->assertEquals(
            [
                HttpResponse::HTTP_OK => $this->userUpdatedResponse,
                HttpResponse::HTTP_GONE => $this->userNotFoundResponse,
                HttpResponse::HTTP_NOT_FOUND => $this->tokenNotFound,
            ],
            $responses
        );
    }

    private function setExpectations(): void
    {
        $this->userUpdatedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userUpdatedResponse);
        $this->userNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userNotFoundResponse);
        $this->tokenNotFoundFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->tokenNotFound);

        $this->postOperation->method('getResponses')->willReturn([
            HttpResponse::HTTP_OK => $this->userUpdatedResponse,
            HttpResponse::HTTP_GONE => $this->userNotFoundResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->tokenNotFound,
        ]);
        $this->paths->method('getPath')->willReturn($this->pathItem);
        $this->openApi->method('getPaths')->willReturn($this->paths);
    }
}
