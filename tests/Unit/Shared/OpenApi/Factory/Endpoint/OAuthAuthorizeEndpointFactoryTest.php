<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\OpenApi\Builder\QueryParameterBuilder;
use App\Shared\OpenApi\Factory\Endpoint\OAuthAuthorizeEndpointFactory;
use App\Shared\OpenApi\Factory\Response\InvalidClientCredentialsResponseFactory;
use App\Shared\OpenApi\Factory\Response\OAuthRedirectResponseFactory;
use App\Shared\OpenApi\Factory\Response\UnsupportedGrantTypeResponseFactory;
use App\Tests\Unit\UnitTestCase;

class OAuthAuthorizeEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $unsupportedFactory = $this->createMock(UnsupportedGrantTypeResponseFactory::class);
        $invalidCredsFactory = $this->createMock(InvalidClientCredentialsResponseFactory::class);
        $redirectResponseFactory = $this->createMock(OAuthRedirectResponseFactory::class);
        $queryParameterBuilder = $this->createMock(QueryParameterBuilder::class);

        $unsupportedResponse = $this->createMock(Response::class);
        $invalidResponse = $this->createMock(Response::class);
        $redirectResponse = $this->createMock(Response::class);

        $unsupportedFactory->expects($this->once())->method('getResponse')->willReturn($unsupportedResponse);
        $invalidCredsFactory->expects($this->once())->method('getResponse')->willReturn($invalidResponse);
        $redirectResponseFactory->expects($this->once())->method('getResponse')->willReturn($redirectResponse);

        $factory = new OAuthAuthorizeEndpointFactory(
            $unsupportedFactory,
            $invalidCredsFactory,
            $redirectResponseFactory,
            $queryParameterBuilder
        );

        $openApi = $this->createMock(OpenApi::class);
        $paths = $this->createMock(Paths::class);
        $openApi->method('getPaths')->willReturn($paths);

        $paths->expects($this->once())
            ->method('addPath')
            ->with('/api/oauth/authorize', $this->isInstanceOf(PathItem::class));

        $factory->createEndpoint($openApi);
    }
}
