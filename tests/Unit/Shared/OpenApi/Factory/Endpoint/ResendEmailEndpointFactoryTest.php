<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\OpenApi\Factory\Endpoint\ResendEmailEndpointFactory;
use App\Shared\OpenApi\Factory\Request\EmptyRequestFactory;
use App\Shared\OpenApi\Factory\Response\EmailSendAgainResponseFactory;
use App\Shared\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\OpenApi\Factory\Response\UserTimedOutResponseFactory;
use App\Shared\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use App\Tests\Unit\UnitTestCase;

class ResendEmailEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $userNotFoundResponseFactory = $this->createMock(UserNotFoundResponseFactory::class);
        $sendAgainResponseFactory = $this->createMock(EmailSendAgainResponseFactory::class);
        $timedOutResponseFactory = $this->createMock(UserTimedOutResponseFactory::class);
        $emptyRequestFactory = $this->createMock(EmptyRequestFactory::class);
        $parameterFactory = $this->createMock(UuidUriParameterFactory::class);

        $userNotFoundResponse = $this->createMock(Response::class);
        $sendAgainResponse = $this->createMock(Response::class);
        $timedOutResponse = $this->createMock(Response::class);

        $userNotFoundResponseFactory->expects($this->once())->method('getResponse')->willReturn($userNotFoundResponse);
        $sendAgainResponseFactory->expects($this->once())->method('getResponse')->willReturn($sendAgainResponse);
        $timedOutResponseFactory->expects($this->once())->method('getResponse')->willReturn($timedOutResponse);
        $emptyRequestFactory->expects($this->once())->method('getRequest');
        $parameterFactory->expects($this->once())->method('getParameter');

        $factory = new ResendEmailEndpointFactory(
            $userNotFoundResponseFactory,
            $sendAgainResponseFactory,
            $timedOutResponseFactory,
            $emptyRequestFactory,
            $parameterFactory
        );

        $openApi = $this->createMock(OpenApi::class);
        $paths = $this->createMock(Paths::class);
        $pathItem = $this->createMock(PathItem::class);
        $operation = $this->createMock(Operation::class);

        $openApi->expects($this->exactly(2))
            ->method('getPaths')
            ->willReturn($paths);

        $paths->expects($this->once())
            ->method('getPath')
            ->with('/api/users/{id}/resend-confirmation-email')
            ->willReturn($pathItem);

        $pathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($operation);

        $pathItem->expects($this->once())
            ->method('withPost')
            ->willReturn($pathItem);

        $paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/users/{id}/resend-confirmation-email',
                $pathItem
            );

        $factory->createEndpoint($openApi);
    }
}
