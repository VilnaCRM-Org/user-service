<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\ResendEmailEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\EmptyRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\EmailSendFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedMediaTypeFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserTimedOutResponseFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use App\Tests\Unit\UnitTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
final class ResendEmailEndpointFactoryTest extends UnitTestCase
{
    private UserNotFoundResponseFactory $userNotFoundResponseFactory;
    private EmailSendFactory $sendAgainResponseFactory;
    private UserTimedOutResponseFactory $timedOutResponseFactory;
    private UnsupportedMediaTypeFactory $unsupportedMediaTypeFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private EmptyRequestFactory $emptyRequestFactory;
    private UuidUriParameterFactory $parameterFactory;
    private Response $userNotFoundResponse;
    private Response $sendAgainResponse;
    private Response $timedOutResponse;
    private Response $unsupportedMediaResponse;
    private Response $badRequestResponse;
    private OpenApi $openApi;
    private Paths $paths;
    private PathItem $pathItem;
    private Operation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userNotFoundResponseFactory =
            $this->createMock(UserNotFoundResponseFactory::class);
        $this->sendAgainResponseFactory =
            $this->createMock(EmailSendFactory::class);
        $this->timedOutResponseFactory =
            $this->createMock(UserTimedOutResponseFactory::class);
        $this->unsupportedMediaTypeFactory =
            $this->createMock(UnsupportedMediaTypeFactory::class);
        $this->badRequestResponseFactory =
            $this->createMock(BadRequestResponseFactory::class);
        $this->emptyRequestFactory =
            $this->createMock(EmptyRequestFactory::class);
        $this->parameterFactory =
            $this->createMock(UuidUriParameterFactory::class);
        $this->userNotFoundResponse = $this->createMock(Response::class);
        $this->sendAgainResponse = $this->createMock(Response::class);
        $this->timedOutResponse = $this->createMock(Response::class);
        $this->unsupportedMediaResponse = $this->createMock(Response::class);
        $this->badRequestResponse = $this->createMock(Response::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->pathItem = $this->createMock(PathItem::class);
        $this->operation = $this->createMock(Operation::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new ResendEmailEndpointFactory(
            getenv('API_PREFIX'),
            $this->userNotFoundResponseFactory,
            $this->sendAgainResponseFactory,
            $this->timedOutResponseFactory,
            $this->unsupportedMediaTypeFactory,
            $this->badRequestResponseFactory,
            $this->emptyRequestFactory,
            $this->parameterFactory
        );

        $factory->createEndpoint($this->openApi);
    }

    private function setExpectations(): void
    {
        $this->userNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userNotFoundResponse);
        $this->sendAgainResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->sendAgainResponse);
        $this->timedOutResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->timedOutResponse);
        $this->unsupportedMediaTypeFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->unsupportedMediaResponse);
        $this->badRequestResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->badRequestResponse);
        $this->emptyRequestFactory->expects($this->once())
            ->method('getRequest');
        $this->parameterFactory->expects($this->once())
            ->method('getParameter');
        $this->openApi->expects($this->exactly(2))
            ->method('getPaths')
            ->willReturn($this->paths);

        $this->setExpectationsForPaths();
        $this->setExpectationsForPathItem();
    }

    private function setExpectationsForPaths(): void
    {
        $endpointUri = '/api/users/{id}/resend-confirmation-email';
        $this->paths->expects($this->once())
            ->method('getPath')
            ->with($endpointUri)
            ->willReturn($this->pathItem);
        $this->paths->expects($this->once())
            ->method('addPath')
            ->with(
                $endpointUri,
                $this->pathItem
            );
    }

    private function setExpectationsForPathItem(): void
    {
        $this->pathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($this->operation);
        $this->pathItem->expects($this->once())
            ->method('withPost')
            ->willReturn($this->pathItem);
    }
}
