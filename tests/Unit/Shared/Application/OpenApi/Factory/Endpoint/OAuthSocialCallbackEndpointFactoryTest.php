<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\OAuthSocialCallbackEndpointFactory;
use App\Tests\Unit\UnitTestCase;

final class OAuthSocialCallbackEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpointAddsSocialCallbackPath(): void
    {
        $paths = $this->createMock(Paths::class);
        $openApi = $this->createMock(OpenApi::class);
        $openApi->method('getPaths')->willReturn($paths);

        $paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/auth/social/{provider}/callback',
                $this->isInstanceOf(PathItem::class),
            );

        $factory = new OAuthSocialCallbackEndpointFactory(getenv('API_PREFIX'));
        $factory->createEndpoint($openApi);
    }
}
