<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Builder\QueryParameterBuilder;
use App\Shared\Application\OpenApi\Factory\Endpoint\OAuthAuthorizeEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Response\InvalidCredentialsFactory;
use App\Shared\Application\OpenApi\Factory\Response\OAuthRedirectFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedTypeFactory;
use App\Tests\Unit\UnitTestCase;

final class OAuthAuthorizeEndpointFactoryTest extends UnitTestCase
{
    private UnsupportedTypeFactory $unsupportedFactory;
    private InvalidCredentialsFactory $invalidCredsFactory;
    private OAuthRedirectFactory $redirectResponseFactory;
    private QueryParameterBuilder $queryParameterBuilder;
    private OpenApi $openApi;
    private Paths $paths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unsupportedFactory =
            $this->createMock(UnsupportedTypeFactory::class);
        $this->invalidCredsFactory =
            $this->createMock(InvalidCredentialsFactory::class);
        $this->redirectResponseFactory =
            $this->createMock(OAuthRedirectFactory::class);
        $this->queryParameterBuilder =
            $this->createMock(QueryParameterBuilder::class);
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new OAuthAuthorizeEndpointFactory(
            $this->unsupportedFactory,
            $this->invalidCredsFactory,
            $this->redirectResponseFactory,
            $this->queryParameterBuilder
        );

        $factory->createEndpoint($this->openApi);
    }

    private function setExpectations(): void
    {
        $this->unsupportedFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));
        $this->invalidCredsFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));
        $this->redirectResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(Response::class));
        $this->openApi->method('getPaths')->willReturn($this->paths);
        $this->paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/oauth/authorize',
                $this->isInstanceOf(PathItem::class)
            );
    }
}
