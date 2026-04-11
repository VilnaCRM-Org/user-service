<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Application\OpenApi\Factory\Endpoint\OAuthSocialInitiateEndpointFactory;
use App\Shared\Application\Provider\OAuthSupportedProvidersProvider;
use App\Tests\Unit\UnitTestCase;

final class OAuthSocialInitiateEndpointFactoryTest extends UnitTestCase
{
    public function testCreateEndpointAddsSocialInitiatePath(): void
    {
        $paths = $this->createMock(Paths::class);
        $openApi = $this->createMock(OpenApi::class);
        $openApi->method('getPaths')->willReturn($paths);

        $paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/auth/social/{provider}',
                $this->isInstanceOf(PathItem::class),
            );

        $factory = new OAuthSocialInitiateEndpointFactory(
            (string) getenv('API_PREFIX'),
            $this->createSupportedProvidersProvider(),
        );
        $factory->createEndpoint($openApi);
    }

    private function createSupportedProvidersProvider(): OAuthSupportedProvidersProvider
    {
        return new OAuthSupportedProvidersProvider(
            array_map(
                static fn (string $name): OAuthProvider => OAuthProvider::fromString($name),
                ['github', 'google', 'facebook', 'twitter'],
            ),
        );
    }
}
