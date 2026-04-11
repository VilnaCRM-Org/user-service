<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Application\Collection\OAuthProviderNameCollection;
use App\Shared\Application\OpenApi\Factory\Endpoint\OAuthSocialCallbackEndpointFactory;
use App\Shared\Application\Provider\OAuthSupportedProvidersProvider;
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

        $factory = new OAuthSocialCallbackEndpointFactory(
            (string) getenv('API_PREFIX'),
            $this->createSupportedProvidersProvider(),
        );
        $factory->createEndpoint($openApi);
    }

    private function createSupportedProvidersProvider(): OAuthSupportedProvidersProvider
    {
        return new OAuthSupportedProvidersProvider(
            new OAuthProviderNameCollection(
                array_map(
                    static fn (string $name): OAuthProvider => OAuthProvider::fromString($name),
                    ['github', 'google', 'facebook', 'twitter'],
                ),
            ),
        );
    }
}
