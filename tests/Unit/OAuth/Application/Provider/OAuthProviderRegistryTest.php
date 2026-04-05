<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Provider;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Application\Provider\OAuthProviderRegistry;
use App\OAuth\Domain\Exception\UnsupportedProviderException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;

final class OAuthProviderRegistryTest extends UnitTestCase
{
    private OAuthProviderRegistry $registry;
    private OAuthProviderCollection $providers;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $supportedProviders = ['github', 'google', 'facebook', 'twitter'];
        $providers = [];

        foreach ($supportedProviders as $name) {
            $mock = $this->createMock(OAuthProviderInterface::class);
            $mock->method('getProvider')
                ->willReturn(OAuthProvider::fromString($name));
            $providers[] = $mock;
        }

        $this->providers = new OAuthProviderCollection(...$providers);
        $this->registry = new OAuthProviderRegistry($this->providers);
    }

    public function testGetReturnsGitHubProvider(): void
    {
        $provider = $this->registry->get('github');

        $this->assertSame($this->providers->get('github'), $provider);
    }

    public function testGetReturnsGoogleProvider(): void
    {
        $provider = $this->registry->get('google');

        $this->assertSame($this->providers->get('google'), $provider);
    }

    public function testGetReturnsFacebookProvider(): void
    {
        $provider = $this->registry->get('facebook');

        $this->assertSame($this->providers->get('facebook'), $provider);
    }

    public function testGetReturnsTwitterProvider(): void
    {
        $provider = $this->registry->get('twitter');

        $this->assertSame($this->providers->get('twitter'), $provider);
    }

    public function testGetThrowsForUnsupportedProvider(): void
    {
        $this->expectException(UnsupportedProviderException::class);

        $this->registry->get('unsupported_provider');
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
