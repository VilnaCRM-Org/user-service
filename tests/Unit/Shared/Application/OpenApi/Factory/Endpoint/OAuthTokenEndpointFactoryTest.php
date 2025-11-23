<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\OAuthTokenEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\OAuthTokenRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\InvalidCredentialsFactory;
use App\Shared\Application\OpenApi\Factory\Response\OAuthTokenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedTypeFactory;
use App\Tests\Unit\UnitTestCase;

final class OAuthTokenEndpointFactoryTest extends UnitTestCase
{
    private UnsupportedTypeFactory $unsupportedFactory;
    private InvalidCredentialsFactory $invalidCredsFactory;
    private OAuthTokenResponseFactory $tokenReturnedResponseFactory;
    private OAuthTokenRequestFactory $tokenRequestFactory;
    private OpenApi $openApi;
    private Paths $paths;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->unsupportedFactory =
            $this->createMock(UnsupportedTypeFactory::class);
        $this->invalidCredsFactory =
            $this->createMock(InvalidCredentialsFactory::class);
        $this->tokenReturnedResponseFactory =
            $this->createMock(OAuthTokenResponseFactory::class);
        $this->tokenRequestFactory =
            $this->createMock(OAuthTokenRequestFactory::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new OAuthTokenEndpointFactory(
            getenv('API_PREFIX'),
            $this->unsupportedFactory,
            $this->invalidCredsFactory,
            $this->tokenReturnedResponseFactory,
            $this->tokenRequestFactory
        );

        $this->openApi->method('getPaths')->willReturn($this->paths);

        $this->paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/oauth/token',
                $this->isInstanceOf(PathItem::class)
            );

        $factory->createEndpoint($this->openApi);
    }

    private function setExpectations(): void
    {
        $this->tokenReturnedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));
        $this->invalidCredsFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));
        $this->unsupportedFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));
        $this->tokenRequestFactory->expects($this->once())
            ->method('getRequest');
    }
}
