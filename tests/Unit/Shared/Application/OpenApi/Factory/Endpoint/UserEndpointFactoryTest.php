<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\UserEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Tests\Unit\UnitTestCase;

class UserEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $validationErrorResponseFactory = $this->createMock(ValidationErrorFactory::class);
        $badRequestResponseFactory = $this->createMock(BadRequestResponseFactory::class);
        $userCreatedResponseFactory = $this->createMock(UserCreatedResponseFactory::class);

        $validationErrorResponse = $this->createMock(Response::class);
        $duplicateEmailResponse = $this->createMock(Response::class);
        $badRequestResponse = $this->createMock(Response::class);
        $userCreatedResponse = $this->createMock(Response::class);

        $validationErrorResponseFactory->expects($this->once())->method('getResponse')->willReturn($validationErrorResponse);
        $badRequestResponseFactory->expects($this->once())->method('getResponse')->willReturn($badRequestResponse);
        $userCreatedResponseFactory->expects($this->once())->method('getResponse')->willReturn($userCreatedResponse);

        $factory = new UserEndpointFactory(
            $validationErrorResponseFactory,
            $badRequestResponseFactory,
            $userCreatedResponseFactory
        );

        $openApi = $this->createMock(OpenApi::class);
        $paths = $this->createMock(Paths::class);
        $pathItem = $this->createMock(PathItem::class);
        $operationPost = $this->createMock(Operation::class);
        $operationGet = $this->createMock(Operation::class);

        $openApi->expects($this->exactly(2))
            ->method('getPaths')
            ->willReturn($paths);

        $paths->expects($this->once())
            ->method('getPath')
            ->with('/api/users')
            ->willReturn($pathItem);

        $pathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($operationPost);

        $pathItem->expects($this->once())
            ->method('getGet')
            ->willReturn($operationGet);

        $pathItem->expects($this->once())
            ->method('withPost')
            ->willReturn($pathItem);

        $pathItem->expects($this->once())
            ->method('withGet')
            ->willReturn($pathItem);

        $paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/users',
                $pathItem
            );

        $factory->createEndpoint($openApi);
    }
}
