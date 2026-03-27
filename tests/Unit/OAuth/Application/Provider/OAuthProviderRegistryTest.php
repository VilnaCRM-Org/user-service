<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Provider;

use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Application\Provider\OAuthProviderRegistry;
use App\OAuth\Domain\Exception\UnsupportedProviderException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;

final class OAuthProviderRegistryTest extends UnitTestCase
{
    private OAuthProviderRegistry $registry;

    /** @var array<string, OAuthProviderInterface> */
    private array $mockProviders = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $supportedProviders = ['github', 'google', 'facebook', 'twitter'];

        foreach ($supportedProviders as $name) {
            $mock = $this->createMock(OAuthProviderInterface::class);
            $mock->method('getProvider')
                ->willReturn(OAuthProvider::fromString($name));
            $this->mockProviders[$name] = $mock;
        }

        $this->registry = new OAuthProviderRegistry($this->mockProviders);
    }

    public function testGetReturnsGitHubProvider(): void
    {
        $provider = $this->registry->get('github');

        $this->assertSame($this->mockProviders['github'], $provider);
    }

    public function testGetReturnsGoogleProvider(): void
    {
        $provider = $this->registry->get('google');

        $this->assertSame($this->mockProviders['google'], $provider);
    }

    public function testGetReturnsFacebookProvider(): void
    {
        $provider = $this->registry->get('facebook');

        $this->assertSame($this->mockProviders['facebook'], $provider);
    }

    public function testGetReturnsTwitterProvider(): void
    {
        $provider = $this->registry->get('twitter');

        $this->assertSame($this->mockProviders['twitter'], $provider);
    }

    public function testGetThrowsForUnsupportedProvider(): void
    {
        $this->expectException(UnsupportedProviderException::class);

        $this->registry->get('unsupported_provider');
    }

    public function testThrowsOnDuplicateProviderRegistration(): void
    {
        $providerName = $this->faker->word();

        $mock1 = $this->createMock(OAuthProviderInterface::class);
        $mock1->method('getProvider')
            ->willReturn(OAuthProvider::fromString($providerName));

        $mock2 = $this->createMock(OAuthProviderInterface::class);
        $mock2->method('getProvider')
            ->willReturn(OAuthProvider::fromString($providerName));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Duplicate OAuth provider registration: %s', $providerName)
        );

        new OAuthProviderRegistry([$mock1, $mock2]);
    }

    public function testSupportedProvidersReturnsAllProviderNames(): void
    {
        $supported = $this->registry->supportedProviders();

        $this->assertCount(4, $supported);
        $this->assertContains('github', $supported);
        $this->assertContains('google', $supported);
        $this->assertContains('facebook', $supported);
        $this->assertContains('twitter', $supported);
    }
}
