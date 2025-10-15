<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\PasswordResetEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmPasswordResetRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\RequestPasswordResetRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class PasswordResetEndpointFactoryTest extends UnitTestCase
{
    private RequestPasswordResetRequestFactory $requestPasswordResetRequestFactory;
    private ConfirmPasswordResetRequestFactory $confirmPasswordResetRequestFactory;
    private RequestBody $requestPasswordResetBody;
    private RequestBody $confirmPasswordResetBody;
    private OpenApi $openApi;
    private Paths $paths;
    private PathItem $requestPathItem;
    private PathItem $confirmPathItem;
    private Operation $requestOperation;
    private Operation $confirmOperation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestPasswordResetRequestFactory = $this->createMock(RequestPasswordResetRequestFactory::class);
        $this->confirmPasswordResetRequestFactory = $this->createMock(ConfirmPasswordResetRequestFactory::class);
        $this->requestPasswordResetBody = $this->createMock(RequestBody::class);
        $this->confirmPasswordResetBody = $this->createMock(RequestBody::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->requestPathItem = $this->createMock(PathItem::class);
        $this->confirmPathItem = $this->createMock(PathItem::class);
        $this->requestOperation = $this->createMock(Operation::class);
        $this->confirmOperation = $this->createMock(Operation::class);
    }

    public function testCreateEndpointInjectsCustomRequests(): void
    {
        $this->requestPasswordResetRequestFactory->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestPasswordResetBody);

        $this->confirmPasswordResetRequestFactory->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->confirmPasswordResetBody);

        $this->openApi->expects($this->atLeast(2))
            ->method('getPaths')
            ->willReturn($this->paths);

        $this->paths->expects($this->exactly(2))
            ->method('getPath')
            ->withConsecutive([
                '/api/reset-password',
            ], [
                '/api/reset-password/confirm',
            ])
            ->willReturnOnConsecutiveCalls($this->requestPathItem, $this->confirmPathItem);

        $this->requestPathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($this->requestOperation);
        $this->confirmPathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($this->confirmOperation);

        $this->requestOperation->expects($this->once())
            ->method('withRequestBody')
            ->with($this->requestPasswordResetBody)
            ->willReturn($this->requestOperation);
        $this->confirmOperation->expects($this->once())
            ->method('withRequestBody')
            ->with($this->confirmPasswordResetBody)
            ->willReturn($this->confirmOperation);

        $this->requestPathItem->expects($this->once())
            ->method('withPost')
            ->with($this->requestOperation)
            ->willReturn($this->requestPathItem);
        $this->confirmPathItem->expects($this->once())
            ->method('withPost')
            ->with($this->confirmOperation)
            ->willReturn($this->confirmPathItem);

        $this->paths->expects($this->exactly(2))
            ->method('addPath')
            ->withConsecutive([
                '/api/reset-password',
                $this->requestPathItem,
            ], [
                '/api/reset-password/confirm',
                $this->confirmPathItem,
            ]);

        $factory = new PasswordResetEndpointFactory(
            getenv('API_PREFIX'),
            $this->requestPasswordResetRequestFactory,
            $this->confirmPasswordResetRequestFactory
        );

        $factory->createEndpoint($this->openApi);
    }
}
