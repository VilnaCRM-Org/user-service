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

class ParametrizedUserEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $validationErrorResponseFactory = $this->createMock(ValidationErrorFactory::class);
        $badRequestResponseFactory = $this->createMock(BadRequestResponseFactory::class);
        $userNotFoundResponseFactory = $this->createMock(UserNotFoundResponseFactory::class);
        $userDeletedResponseFactory = $this->createMock(UserDeletedResponseFactory::class);
        $uuidUriParameterFactory = $this->createMock(UuidUriParameterFactory::class);
        $userUpdatedResponseFactory = $this->createMock(UserUpdatedResponseFactory::class);

        $validationErrorResponse = $this->createMock(Response::class);
        $duplicateEmailResponse = $this->createMock(Response::class);
        $badRequestResponse = $this->createMock(Response::class);
        $userNotFoundResponse = $this->createMock(Response::class);
        $userDeletedResponse = $this->createMock(Response::class);
        $userUpdatedResponse = $this->createMock(Response::class);

        $validationErrorResponseFactory->expects($this->once())->method('getResponse')->willReturn($validationErrorResponse);
        $badRequestResponseFactory->expects($this->once())->method('getResponse')->willReturn($badRequestResponse);
        $userNotFoundResponseFactory->expects($this->once())->method('getResponse')->willReturn($userNotFoundResponse);
        $userDeletedResponseFactory->expects($this->once())->method('getResponse')->willReturn($userDeletedResponse);
        $userUpdatedResponseFactory->expects($this->once())->method('getResponse')->willReturn($userUpdatedResponse);

        $factory = new ParametrizedUserEndpointFactory(
            $validationErrorResponseFactory,
            $badRequestResponseFactory,
            $userNotFoundResponseFactory,
            $userDeletedResponseFactory,
            $uuidUriParameterFactory,
            $userUpdatedResponseFactory,
        );

        $openApi = $this->createMock(OpenApi::class);
        $paths = $this->createMock(Paths::class);
        $pathItem = $this->createMock(PathItem::class);
        $operationPut = $this->createMock(Operation::class);
        $operationPatch = $this->createMock(Operation::class);
        $operationGet = $this->createMock(Operation::class);
        $operationDelete = $this->createMock(Operation::class);

        $openApi->expects($this->exactly(8))
            ->method('getPaths')
            ->willReturn($paths);

        $paths->expects($this->exactly(4))
            ->method('getPath')
            ->with('/api/users/{id}')
            ->willReturn($pathItem);

        $pathItem->expects($this->exactly(1))
            ->method('getPut')
            ->willReturn($operationPut);

        $pathItem->expects($this->exactly(1))
            ->method('getPatch')
            ->willReturn($operationPatch);

        $pathItem->expects($this->exactly(1))
            ->method('getGet')
            ->willReturn($operationGet);

        $pathItem->expects($this->exactly(1))
            ->method('getDelete')
            ->willReturn($operationDelete);

        $paths->expects($this->exactly(4))
            ->method('addPath')
            ->withConsecutive(
                ['/api/users/{id}', $this->isInstanceOf(PathItem::class)],
                ['/api/users/{id}', $this->isInstanceOf(PathItem::class)],
                ['/api/users/{id}', $this->isInstanceOf(PathItem::class)],
                ['/api/users/{id}', $this->isInstanceOf(PathItem::class)]
            );

        $factory->createEndpoint($openApi);
    }
}
