<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Infrastructure\Provider\DeterministicOAuthProvider;
use App\Tests\Unit\UnitTestCase;

final class DeterministicOAuthProviderTest extends UnitTestCase
{
    private DeterministicOAuthProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new DeterministicOAuthProvider(
            OAuthProvider::fromString('github'),
            true,
            false,
        );
    }

    public function testReturnsConfiguredProviderFlags(): void
    {
        $this->assertSame('github', (string) $this->provider->getProvider());
        $this->assertTrue($this->provider->supportsPkce());
        $this->assertTrue($this->provider->emailAlwaysVerified());
        $this->assertFalse($this->provider->requiresExtraProfileCall());
    }

    public function testBuildsAuthorizationUrlWithPkceChallenge(): void
    {
        $state = $this->faker->uuid();
        $challenge = $this->faker->sha256();

        $url = $this->provider->getAuthorizationUrl($state, $challenge);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringContainsString('oauth.mock.example/github/authorize', $url);
        $this->assertStringContainsString(sprintf('state=%s', $state), $url);
        $this->assertStringContainsString(sprintf('code_challenge=%s', $challenge), $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);
        $this->assertSame('github', $query['provider']);
    }

    public function testBuildsAuthorizationUrlWithoutPkceChallenge(): void
    {
        $state = $this->faker->uuid();

        $url = $this->provider->getAuthorizationUrl($state, null);

        $this->assertStringContainsString('oauth.mock.example/github/authorize', $url);
        $this->assertStringContainsString(sprintf('state=%s', $state), $url);
        $this->assertStringNotContainsString('code_challenge=', $url);
        $this->assertStringNotContainsString('code_challenge_method=', $url);
    }

    public function testFetchProfileReturnsDeterministicProfile(): void
    {
        $profile = $this->provider->fetchProfile(
            $this->provider->exchangeCode('new-user', null),
        );

        $this->assertSame(
            'github-new-user@oauth.example.test',
            $profile->email,
        );
        $this->assertSame('New user', $profile->name);
        $this->assertSame('github-new-user-id', $profile->providerId);
        $this->assertTrue($profile->emailVerified);
    }

    public function testEmailHelpersSlugifyMixedCaseAndSpacing(): void
    {
        $this->assertSame(
            'github-fancy-user@oauth.example.test',
            DeterministicOAuthProvider::emailFor('GitHub', ' Fancy User '),
        );
        $this->assertSame(
            'github-fancy-user-id',
            DeterministicOAuthProvider::providerIdFor('GitHub', ' Fancy User '),
        );
    }

    public function testFetchProfileFallsBackToProviderSlugWithoutDelimiter(): void
    {
        $profile = $this->provider->fetchProfile('github');

        $this->assertSame('github-github@oauth.example.test', $profile->email);
        $this->assertSame('Github', $profile->name);
        $this->assertSame('github-github-id', $profile->providerId);
    }

    public function testFetchProfilePreservesScenarioTailAfterFirstDelimiter(): void
    {
        $profile = $this->provider->fetchProfile('github:team%20lead:eu');

        $this->assertSame(
            'github-team-lead-eu@oauth.example.test',
            $profile->email,
        );
        $this->assertSame('Team lead eu', $profile->name);
        $this->assertSame('github-team-lead-eu-id', $profile->providerId);
    }

    public function testExchangeCodeThrowsWhenProviderFails(): void
    {
        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessage('Mock provider token exchange failed.');

        $this->provider->exchangeCode('provider-error', null);
    }

    public function testFetchProfileThrowsWhenEmailIsUnavailable(): void
    {
        $this->expectException(OAuthEmailUnavailableException::class);

        $this->provider->fetchProfile($this->provider->exchangeCode('no-email', null));
    }

    public function testFetchProfileThrowsWhenEmailIsUnverified(): void
    {
        $this->expectException(UnverifiedProviderEmailException::class);

        $this->provider->fetchProfile(
            $this->provider->exchangeCode('unverified-email', null),
        );
    }
}
