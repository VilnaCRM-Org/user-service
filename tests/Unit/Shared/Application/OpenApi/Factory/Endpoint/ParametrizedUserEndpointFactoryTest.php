<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\ParametrizedUserEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationErrorResponseFactory =
            $this->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory =
            $this->createMock(BadRequestResponseFactory::class);
        $this->userNotFoundResponseFactory =
            $this->createMock(UserNotFoundResponseFactory::class);
        $this->userDeletedResponseFactory =
            $this->createMock(UserDeletedResponseFactory::class);
        $this->uuidUriParameterFactory =
            $this->createMock(UuidUriParameterFactory::class);
        $this->userUpdatedResponseFactory =
            $this->createMock(UserUpdatedResponseFactory::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->pathItem = $this->createMock(PathItem::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new ParametrizedUserEndpointFactory(
            $this->validationErrorResponseFactory,
            $this->badRequestResponseFactory,
            $this->userNotFoundResponseFactory,
            $this->userDeletedResponseFactory,
            $this->uuidUriParameterFactory,
            $this->userUpdatedResponseFactory,
        );

        $factory->createEndpoint($this->openApi);
    }

    private function setExpectations(): void
    {
        $this->validationErrorResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));

        $this->badRequestResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));

        $this->userNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));

        $this->userDeletedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));

        $this->userUpdatedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));

        $this->openApi->expects($this->exactly(8))
            ->method('getPaths')
            ->willReturn($this->paths);

        $this->setExpectationsFotPaths();
        $this->setExpectationsFotPathItem();
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
            ->withConsecutive(
                [$endpointUri, $this->isInstanceOf(PathItem::class)],
                [$endpointUri, $this->isInstanceOf(PathItem::class)],
                [$endpointUri, $this->isInstanceOf(PathItem::class)],
                [$endpointUri, $this->isInstanceOf(PathItem::class)],
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
