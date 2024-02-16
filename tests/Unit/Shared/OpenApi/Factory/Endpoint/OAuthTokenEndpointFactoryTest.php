<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\OpenApi\Factory\Endpoint\OAuthTokenEndpointFactory;
use App\Shared\OpenApi\Factory\Request\OAuthTokenRequestFactory;
use App\Shared\OpenApi\Factory\Response\InvalidClientCredentialsResponseFactory;
use App\Shared\OpenApi\Factory\Response\OAuthTokenReturnedResponseFactory;
use App\Shared\OpenApi\Factory\Response\UnsupportedGrantTypeResponseFactory;
use App\Tests\Unit\UnitTestCase;

class OAuthTokenEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $unsupportedFactory = $this->createMock(UnsupportedGrantTypeResponseFactory::class);
        $invalidCredsFactory = $this->createMock(InvalidClientCredentialsResponseFactory::class);
        $tokenReturnedResponseFactory = $this->createMock(OAuthTokenReturnedResponseFactory::class);
        $tokenRequestFactory = $this->createMock(OAuthTokenRequestFactory::class);

        $tokenResponse = $this->createMock(Response::class);
        $invalidResponse = $this->createMock(Response::class);
        $unsupportedResponse = $this->createMock(Response::class);

        $tokenReturnedResponseFactory->expects($this->once())->method('getResponse')->willReturn($tokenResponse);
        $invalidCredsFactory->expects($this->once())->method('getResponse')->willReturn($invalidResponse);
        $unsupportedFactory->expects($this->once())->method('getResponse')->willReturn($unsupportedResponse);
        $tokenRequestFactory->expects($this->once())->method('getRequest');

        $factory = new OAuthTokenEndpointFactory(
            $unsupportedFactory,
            $invalidCredsFactory,
            $tokenReturnedResponseFactory,
            $tokenRequestFactory
        );

        $openApi = $this->createMock(OpenApi::class);
        $paths = $this->createMock(Paths::class);
        $openApi->method('getPaths')->willReturn($paths);

        $paths->expects($this->once())
            ->method('addPath')
            ->with('/api/oauth/token', $this->isInstanceOf(PathItem::class));

        $factory->createEndpoint($openApi);
    }
}
