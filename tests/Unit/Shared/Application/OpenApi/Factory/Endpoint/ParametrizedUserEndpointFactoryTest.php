<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\ParamUserEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\ReplaceUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\UpdateUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use App\Shared\Application\Provider\OpenApi\ParamUserResponseProvider;
use App\Tests\Unit\UnitTestCase;

final class ParametrizedUserEndpointFactoryTest extends UnitTestCase
{
    private ValidationErrorFactory $validationErrorResponseFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private UserNotFoundResponseFactory $userNotFoundResponseFactory;
    private UserDeletedResponseFactory $userDeletedResponseFactory;
    private UuidUriParameterFactory $uuidUriParameterFactory;
    private UserUpdatedResponseFactory $userUpdatedResponseFactory;
    private OpenApi $openApi;
    private Paths $paths;
    private PathItem $pathItem;
    private UserReturnedResponseFactory $userReturnedResponseFactory;
    private ReplaceUserRequestFactory $replaceUserRequestFactory;
    private UpdateUserRequestFactory $updateUserRequestFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeMocks();
        $this->initializeOpenApiMocks();
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();
        $factory = $this->createParamUserEndpointFactory();
        $factory->createEndpoint($this->openApi);
    }

    private function initializeMocks(): void
    {
        $this->validationErrorResponseFactory = $this->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory = $this->createMock(BadRequestResponseFactory::class);
        $this->userNotFoundResponseFactory = $this->createMock(UserNotFoundResponseFactory::class);
        $this->userDeletedResponseFactory = $this->createMock(UserDeletedResponseFactory::class);
        $this->uuidUriParameterFactory = $this->createMock(UuidUriParameterFactory::class);
        $this->userUpdatedResponseFactory = $this->createMock(UserUpdatedResponseFactory::class);
        $this->userReturnedResponseFactory = $this->createMock(UserReturnedResponseFactory::class);
        $this->replaceUserRequestFactory = $this->createMock(ReplaceUserRequestFactory::class);
        $this->updateUserRequestFactory = $this->createMock(UpdateUserRequestFactory::class);
    }

    private function initializeOpenApiMocks(): void
    {
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->pathItem = $this->createMock(PathItem::class);
    }

    private function createParamUserEndpointFactory(): ParamUserEndpointFactory
    {
        $responseProvider = new ParamUserResponseProvider(
            $this->validationErrorResponseFactory,
            $this->badRequestResponseFactory,
            $this->userNotFoundResponseFactory,
            $this->userDeletedResponseFactory,
            $this->userUpdatedResponseFactory,
            $this->userReturnedResponseFactory
        );

        return new ParamUserEndpointFactory(
            getenv('API_PREFIX'),
            $responseProvider,
            $this->uuidUriParameterFactory,
            $this->replaceUserRequestFactory,
            $this->updateUserRequestFactory
        );
    }

    private function setExpectations(): void
    {
        $this->setResponseFactoryExpectations();
        $this->setRequestFactoryExpectations();
        $this->openApi->expects($this->exactly(8))->method('getPaths')->willReturn($this->paths);
        $this->setExpectationsFotPaths();
        $this->setExpectationsFotPathItem();
    }

    private function setResponseFactoryExpectations(): void
    {
        $this->validationErrorResponseFactory->expects($this->once())
            ->method('getResponse')->willReturn($this->createMock(Response::class));
        $this->badRequestResponseFactory->expects($this->once())
            ->method('getResponse')->willReturn($this->createMock(Response::class));
        $this->userNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')->willReturn($this->createMock(Response::class));
        $this->userDeletedResponseFactory->expects($this->once())
            ->method('getResponse')->willReturn($this->createMock(Response::class));
        $this->userUpdatedResponseFactory->expects($this->once())
            ->method('getResponse')->willReturn($this->createMock(Response::class));
        $this->userReturnedResponseFactory->expects($this->once())
            ->method('getResponse')->willReturn($this->createMock(Response::class));
    }

    private function setRequestFactoryExpectations(): void
    {
        $this->replaceUserRequestFactory->expects($this->once())->method('getRequest');
        $this->updateUserRequestFactory->expects($this->once())->method('getRequest');
    }

    private function setExpectationsFotPaths(): void
    {
        $endpointUri = '/api/users/{id}';
        $this->paths->expects($this->exactly(4))
            ->method('getPath')
            ->with($endpointUri)
            ->willReturn($this->pathItem);

        $this->paths->expects($this->exactly(4))
            ->method('addPath')
            ->willReturnCallback(
                function (string $uri, PathItem $pathItem) use ($endpointUri): void {
                    $this->assertSame($endpointUri, $uri);
                    $this->assertInstanceOf(PathItem::class, $pathItem);
                }
            );
    }

    private function setExpectationsFotPathItem(): void
    {
        $this->pathItem->expects($this->exactly(1))
            ->method('getPut')
            ->willReturn($this->createMock(Operation::class));

        $this->pathItem->expects($this->exactly(1))
            ->method('getPatch')
            ->willReturn($this->createMock(Operation::class));

        $this->pathItem->expects($this->exactly(1))
            ->method('getGet')
            ->willReturn($this->createMock(Operation::class));

        $this->pathItem->expects($this->exactly(1))
            ->method('getDelete')
            ->willReturn($this->createMock(Operation::class));
    }
}
