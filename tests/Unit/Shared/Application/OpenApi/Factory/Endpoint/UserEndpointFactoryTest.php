<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\UserEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CreateUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UsersReturnedFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Tests\Unit\UnitTestCase;

final class UserEndpointFactoryTest extends UnitTestCase
{
    private ValidationErrorFactory $validationErrorResponseFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private UnauthorizedResponseFactory $unauthorizedResponseFactory;
    private UserCreatedResponseFactory $userCreatedResponseFactory;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $unauthorizedResponse;
    private Response $userCreatedResponse;
    private OpenApi $openApi;
    private Paths $paths;
    private PathItem $pathItem;
    private Operation $operationPost;
    private Operation $operationGet;

    private CreateUserRequestFactory $createUserRequestFactory;
    private UsersReturnedFactory $usersReturnedResponseFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeFactoryMocks();
        $this->initializeResponseMocks();
        $this->initializeOpenApiMocks();
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new UserEndpointFactory(
            getenv('API_PREFIX'),
            $this->validationErrorResponseFactory,
            $this->badRequestResponseFactory,
            $this->unauthorizedResponseFactory,
            $this->userCreatedResponseFactory,
            $this->createUserRequestFactory,
            $this->usersReturnedResponseFactory
        );

        $factory->createEndpoint($this->openApi);
    }

    private function initializeFactoryMocks(): void
    {
        $this->validationErrorResponseFactory =
            $this->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory =
            $this->createMock(BadRequestResponseFactory::class);
        $this->unauthorizedResponseFactory =
            $this->createMock(UnauthorizedResponseFactory::class);
        $this->userCreatedResponseFactory =
            $this->createMock(UserCreatedResponseFactory::class);
        $this->createUserRequestFactory =
            $this->createMock(CreateUserRequestFactory::class);
        $this->usersReturnedResponseFactory =
            $this->createMock(UsersReturnedFactory::class);
    }

    private function initializeResponseMocks(): void
    {
        $this->validationErrorResponse = $this->createMock(Response::class);
        $this->badRequestResponse = $this->createMock(Response::class);
        $this->unauthorizedResponse = $this->createMock(Response::class);
        $this->userCreatedResponse = $this->createMock(Response::class);
    }

    private function initializeOpenApiMocks(): void
    {
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->pathItem = $this->createMock(PathItem::class);
        $this->operationPost = $this->createMock(Operation::class);
        $this->operationGet = $this->createMock(Operation::class);
    }

    private function setExpectations(): void
    {
        $this->validationErrorResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->validationErrorResponse);
        $this->badRequestResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->badRequestResponse);
        $this->unauthorizedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->unauthorizedResponse);
        $this->userCreatedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userCreatedResponse);

        $this->openApi->expects($this->exactly(2))
            ->method('getPaths')
            ->willReturn($this->paths);

        $this->setExpectationsForPaths();
        $this->setExpectationsForPathItem();
    }

    private function setExpectationsForPathItem(): void
    {
        $this->pathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($this->operationPost);

        $this->pathItem->expects($this->once())
            ->method('getGet')
            ->willReturn($this->operationGet);

        $this->pathItem->expects($this->once())
            ->method('withPost')
            ->willReturn($this->pathItem);

        $this->pathItem->expects($this->once())
            ->method('withGet')
            ->willReturn($this->pathItem);
    }

    private function setExpectationsForPaths(): void
    {
        $this->paths->expects($this->once())
            ->method('getPath')
            ->with('/api/users')
            ->willReturn($this->pathItem);

        $this->paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/users',
                $this->pathItem
            );
    }
}
