<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\OAuth\Infrastructure\Provider\TwitterOAuthProvider;
use App\Tests\Unit\UnitTestCase;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Smolblog\OAuth2\Client\Provider\Twitter;
use Smolblog\OAuth2\Client\Provider\TwitterUser;

final class TwitterOAuthProviderTest extends UnitTestCase
{
    private Twitter $twitter;
    private TwitterOAuthProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->twitter = $this->createMock(Twitter::class);
        $this->provider = new TwitterOAuthProvider(
            $this->twitter,
            OAuthProvider::fromString('twitter'),
        );
    }

    public function testGetProviderReturnsTwitter(): void
    {
        $this->assertSame('twitter', (string) $this->provider->getProvider());
    }

    public function testSupportsPkceReturnsTrue(): void
    {
        $this->assertTrue($this->provider->supportsPkce());
    }

    public function testEmailAlwaysVerifiedReturnsFalse(): void
    {
        $this->assertFalse($this->provider->emailAlwaysVerified());
    }

    public function testRequiresExtraProfileCallReturnsTrue(): void
    {
        $this->assertTrue($this->provider->requiresExtraProfileCall());
    }

    public function testGetAuthorizationUrlIncludesPkceParams(): void
    {
        $state = $this->faker->uuid();
        $codeChallenge = $this->faker->sha256();
        $expectedUrl = 'https://twitter.com/i/oauth2/authorize?test=1';

        $this->twitter->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state, $codeChallenge): bool {
                    return $options['state'] === $state
                        && $options['code_challenge'] === $codeChallenge
                        && $options['code_challenge_method'] === 'S256'
                        && $options['scope'] === ['tweet.read', 'users.email', 'users.read'];
                }
            ))
            ->willReturn($expectedUrl);

        $url = $this->provider->getAuthorizationUrl($state, $codeChallenge);

        $this->assertSame($expectedUrl, $url);
    }

    public function testGetAuthorizationUrlWithoutPkce(): void
    {
        $state = $this->faker->uuid();

        $this->twitter->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state): bool {
                    return $options['state'] === $state
                        && !isset($options['code_challenge'])
                        && !isset($options['code_challenge_method']);
                }
            ))
            ->willReturn('https://twitter.com/i/oauth2/authorize');

        $this->provider->getAuthorizationUrl($state, null);
    }

    public function testExchangeCodeReturnsAccessToken(): void
    {
        $code = $this->faker->sha256();
        $codeVerifier = $this->faker->sha256();
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->twitter->expects($this->once())
            ->method('setPkceCode')
            ->with($codeVerifier);

        $this->twitter->expects($this->once())
            ->method('getAccessToken')
            ->with('authorization_code', ['code' => $code])
            ->willReturn($accessToken);

        $this->assertSame(
            $expectedToken,
            $this->provider->exchangeCode($code, $codeVerifier),
        );
    }

    public function testExchangeCodeThrowsOAuthProviderExceptionOnFailure(): void
    {
        $invalidCode = $this->faker->sha256();

        $this->twitter->method('getAccessToken')
            ->willThrowException(
                new IdentityProviderException('invalid_grant', 400, [])
            );

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/twitter/');

        $this->provider->exchangeCode($invalidCode, null);
    }

    public function testFetchProfileReturnsProfileWithEmail(): void
    {
        $email = $this->faker->safeEmail();
        $name = $this->faker->name();
        $username = $this->faker->userName();
        $id = $this->faker->uuid();

        $owner = $this->createMock(TwitterUser::class);
        $owner->method('getEmail')->willReturn($email);
        $owner->method('getName')->willReturn($name);
        $owner->method('getUsername')->willReturn($username);
        $owner->method('getId')->willReturn($id);

        $this->twitter->method('getResourceOwner')->willReturn($owner);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertInstanceOf(OAuthUserProfile::class, $profile);
        $this->assertSame($email, $profile->email);
        $this->assertSame($name, $profile->name);
        $this->assertSame($id, $profile->providerId);
        $this->assertFalse($profile->emailVerified);
    }

    public function testFetchProfileThrowsEmailUnavailableWhenEmailIsNull(): void
    {
        $owner = $this->createMock(TwitterUser::class);
        $owner->method('getEmail')->willReturn(null);
        $owner->method('getName')->willReturn($this->faker->name());
        $owner->method('getId')->willReturn($this->faker->uuid());

        $this->twitter->method('getResourceOwner')->willReturn($owner);

        $this->expectException(OAuthEmailUnavailableException::class);
        $this->expectExceptionMessageMatches('/twitter/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testFetchProfileThrowsOAuthProviderExceptionOnApiError(): void
    {
        $this->twitter->method('getResourceOwner')
            ->willThrowException(new \RuntimeException('API unavailable'));

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/twitter/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testExchangeCodeWithoutPkceVerifier(): void
    {
        $code = $this->faker->sha256();
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->twitter->expects($this->never())->method('setPkceCode');
        $this->twitter->method('getAccessToken')->willReturn($accessToken);

        $this->assertSame(
            $expectedToken,
            $this->provider->exchangeCode($code, null),
        );
    }

    public function testExchangeCodeDoesNotReusePkceVerifierAcrossCalls(): void
    {
        $twitter = new StatefulTwitterProviderDouble();
        $provider = new TwitterOAuthProvider(
            $twitter,
            OAuthProvider::fromString('twitter'),
        );

        $firstCode = $this->faker->sha256();
        $firstVerifier = $this->faker->sha256();
        $secondCode = $this->faker->sha256();

        $this->assertSame(
            sprintf('%s|%s', $firstCode, $firstVerifier),
            $provider->exchangeCode($firstCode, $firstVerifier),
        );
        $this->assertSame(
            sprintf('%s|none', $secondCode),
            $provider->exchangeCode($secondCode, null),
        );
    }

    public function testFetchProfileUsesUsernameWhenNameIsNull(): void
    {
        $email = $this->faker->safeEmail();
        $username = $this->faker->userName();

        $owner = $this->createMock(TwitterUser::class);
        $owner->method('getEmail')->willReturn($email);
        $owner->method('getName')->willReturn(null);
        $owner->method('getUsername')->willReturn($username);
        $owner->method('getId')->willReturn($this->faker->uuid());

        $this->twitter->method('getResourceOwner')->willReturn($owner);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertSame($username, $profile->name);
    }

    public function testFetchProfilePrefersNameOverUsernameWhenBothAreAvailable(): void
    {
        $email = $this->faker->safeEmail();
        $name = $this->faker->name();
        $username = $this->faker->userName();

        $owner = $this->createMock(TwitterUser::class);
        $owner->method('getEmail')->willReturn($email);
        $owner->method('getName')->willReturn($name);
        $owner->method('getUsername')->willReturn($username);
        $owner->method('getId')->willReturn($this->faker->uuid());

        $this->twitter->method('getResourceOwner')->willReturn($owner);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertSame($name, $profile->name);
    }
}
