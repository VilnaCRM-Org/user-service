<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Endpoint;

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

class OAuthTokenEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpoint(): void
    {
        $unsupportedFactory = $this->createMock(UnsupportedTypeFactory::class);
        $invalidCredsFactory = $this->createMock(InvalidCredentialsFactory::class);
        $tokenReturnedResponseFactory = $this->createMock(OAuthTokenResponseFactory::class);
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
