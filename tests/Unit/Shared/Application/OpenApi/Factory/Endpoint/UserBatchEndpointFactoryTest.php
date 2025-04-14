<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\UserBatchEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CreateBatchRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\UsersReturnedFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Tests\Unit\UnitTestCase;

final class UserBatchEndpointFactoryTest extends UnitTestCase
{
    private ValidationErrorFactory $validationErrorResponseFactory;
    private UsersReturnedFactory $usersReturnedResponseFactory;
    private CreateBatchRequestFactory $batchRequestFactory;
    private OpenApi $openApi;
    private UserBatchEndpointFactory $factory;
    private Response $validationErrorResponse;
    private Response $usersReturnedResponse;
    private RequestBody $batchRequest;
    private PathItem $pathItem;
    private Operation $operationPost;
    private Paths $paths;

    protected function setUp(): void
    {
        $this->validationErrorResponseFactory =
            $this->createMock(ValidationErrorFactory::class);
        $this->usersReturnedResponseFactory =
            $this->createMock(UsersReturnedFactory::class);
        $this->batchRequestFactory =
            $this->createMock(CreateBatchRequestFactory::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->validationErrorResponse = $this->createMock(Response::class);
        $this->usersReturnedResponse = $this->createMock(Response::class);
        $this->batchRequest = $this->createMock(RequestBody::class);
        $this->pathItem = $this->createMock(PathItem::class);
        $this->operationPost = $this->createMock(Operation::class);
        $this->paths = $this->createMock(Paths::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->factory = new UserBatchEndpointFactory(
            getenv('API_PREFIX'),
            $this->validationErrorResponseFactory,
            $this->usersReturnedResponseFactory,
            $this->batchRequestFactory
        );

        $this->validationErrorResponseFactory->method('getResponse')
            ->willReturn($this->validationErrorResponse);
        $this->usersReturnedResponseFactory->method('getResponse')
            ->willReturn($this->usersReturnedResponse);
        $this->batchRequestFactory->method('getRequest')
            ->willReturn($this->batchRequest);

        $this->setExpectations();

        $this->factory->createEndpoint($this->openApi);
    }

    private function setExpectations(): void
    {
        $this->openApi->method('getPaths')->willReturn($this->paths);

        $this->paths->expects($this->once())
            ->method('getPath')
            ->with('/api/users/batch')
            ->willReturn($this->pathItem);

        $this->pathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($this->operationPost);

        $this->operationPost->expects($this->once())
            ->method('withResponses')
            ->willReturn($this->operationPost);

        $this->operationPost->expects($this->once())
            ->method('withRequestBody')
            ->willReturn($this->operationPost);

        $this->paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/users/batch',
                $this->pathItem->withPost($this->operationPost)
            );
    }
}
