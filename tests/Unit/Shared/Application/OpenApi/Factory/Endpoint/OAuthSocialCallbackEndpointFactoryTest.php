<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Application\Provider\OAuthProviderRegistry;
use App\OAuth\Domain\ValueObject\OAuthProvider;
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

        $factory = new OAuthSocialCallbackEndpointFactory(
            (string) getenv('API_PREFIX'),
            $this->createProviderRegistry(),
        );
        $factory->createEndpoint($openApi);
    }

    private function createProviderRegistry(): OAuthProviderRegistry
    {
        $providers = array_map(
            fn (string $name): OAuthProviderInterface => $this->createProvider($name),
            ['github', 'google', 'facebook', 'twitter'],
        );

        return new OAuthProviderRegistry(new OAuthProviderCollection(...$providers));
    }

    private function createProvider(string $name): OAuthProviderInterface
    {
        $provider = $this->createMock(OAuthProviderInterface::class);
        $provider->method('getProvider')
            ->willReturn(OAuthProvider::fromString($name));

        return $provider;
    }
}
