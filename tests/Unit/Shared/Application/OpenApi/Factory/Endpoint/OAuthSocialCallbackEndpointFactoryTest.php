<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Application\OpenApi\Factory\Endpoint\OAuthSocialCallbackEndpointFactory;
use App\Shared\Application\Provider\OAuthSupportedProvidersProvider;
use App\Shared\Domain\Collection\OAuthProviderNameCollection;
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
            '/api',
            $this->createSupportedProvidersProvider(),
        );
        $factory->createEndpoint($openApi);
    }

    public function testCreateEndpointDocumentsDuplicateEmailConflict(): void
    {
        $paths = $this->createMock(Paths::class);
        $openApi = $this->createMock(OpenApi::class);
        $openApi->method('getPaths')->willReturn($paths);

        $paths->expects($this->once())
            ->method('addPath')
            ->with(
                '/api/auth/social/{provider}/callback',
                $this->callback(
                    fn (PathItem $pathItem): bool => $this
                        ->assertDuplicateEmailConflictDocumented($pathItem)
                ),
            );

        $factory = new OAuthSocialCallbackEndpointFactory(
            '/api',
            $this->createSupportedProvidersProvider(),
        );
        $factory->createEndpoint($openApi);
    }

    private function assertDuplicateEmailConflictDocumented(PathItem $pathItem): bool
    {
        $operation = $pathItem->getGet();
        $this->assertNotNull($operation);
        $responses = $operation->getResponses();
        $this->assertIsArray($responses);
        $this->assertArrayHasKey(409, $responses);

        $content = $responses[409]->getContent();
        $this->assertNotNull($content);
        $mediaType = $content['application/problem+json'] ?? null;
        $this->assertNotNull($mediaType);
        $this->assertSame('duplicate_email', $mediaType->getExample()['error_code'] ?? null);
        $this->assertSame(
            'Email address matches multiple local users; automatic linking is blocked.',
            $mediaType->getExample()['detail'] ?? null
        );

        return true;
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
