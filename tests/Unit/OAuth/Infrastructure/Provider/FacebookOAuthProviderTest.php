<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\OAuth\Infrastructure\Provider\FacebookOAuthProvider;
use App\Tests\Unit\UnitTestCase;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Token\AccessToken;

final class FacebookOAuthProviderTest extends UnitTestCase
{
    private Facebook $facebook;
    private FacebookOAuthProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->facebook = $this->createMock(Facebook::class);
        $this->provider = new FacebookOAuthProvider(
            $this->facebook,
            OAuthProvider::fromString('facebook'),
        );
    }

    public function testGetProviderReturnsFacebook(): void
    {
        $this->assertSame('facebook', (string) $this->provider->getProvider());
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
        $expectedUrl = 'https://www.facebook.com/v18.0/dialog/oauth?test=1';

        $this->facebook->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state, $codeChallenge): bool {
                    return $options['state'] === $state
                        && $options['code_challenge'] === $codeChallenge
                        && $options['code_challenge_method'] === 'S256'
                        && $options['scope'] === ['email'];
                }
            ))
            ->willReturn($expectedUrl);

        $url = $this->provider->getAuthorizationUrl($state, $codeChallenge);

        $this->assertSame($expectedUrl, $url);
    }

    public function testGetAuthorizationUrlWithoutPkce(): void
    {
        $state = $this->faker->uuid();

        $this->facebook->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state): bool {
                    return $options['state'] === $state
                        && !isset($options['code_challenge'])
                        && !isset($options['code_challenge_method']);
                }
            ))
            ->willReturn('https://www.facebook.com/v18.0/dialog/oauth');

        $this->provider->getAuthorizationUrl($state, null);
    }

    public function testExchangeCodeReturnsAccessToken(): void
    {
        $code = $this->faker->sha256();
        $codeVerifier = $this->faker->sha256();
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->facebook->expects($this->once())
            ->method('setPkceCode')
            ->with($codeVerifier);

        $this->facebook->expects($this->once())
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

        $this->facebook->method('getAccessToken')
            ->willThrowException(
                new IdentityProviderException('bad_code', 400, [])
            );

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/facebook/');

        $this->provider->exchangeCode($invalidCode, null);
    }

    public function testFetchProfileReturnsProfileWithEmail(): void
    {
        $email = $this->faker->safeEmail();
        $name = $this->faker->name();
        $id = $this->faker->uuid();

        $owner = $this->createMock(FacebookUser::class);
        $owner->method('getEmail')->willReturn($email);
        $owner->method('getName')->willReturn($name);
        $owner->method('getId')->willReturn($id);

        $this->facebook->method('getResourceOwner')->willReturn($owner);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertInstanceOf(OAuthUserProfile::class, $profile);
        $this->assertSame($email, $profile->email);
        $this->assertSame($name, $profile->name);
        $this->assertSame($id, $profile->providerId);
        $this->assertFalse($profile->emailVerified);
    }

    public function testFetchProfileThrowsEmailUnavailableWhenEmailIsNull(): void
    {
        $owner = $this->createMock(FacebookUser::class);
        $owner->method('getEmail')->willReturn(null);
        $owner->method('getName')->willReturn($this->faker->name());
        $owner->method('getId')->willReturn($this->faker->uuid());

        $this->facebook->method('getResourceOwner')->willReturn($owner);

        $this->expectException(OAuthEmailUnavailableException::class);
        $this->expectExceptionMessageMatches('/facebook/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testFetchProfileThrowsOAuthProviderExceptionOnApiError(): void
    {
        $this->facebook->method('getResourceOwner')
            ->willThrowException(new \RuntimeException('API unavailable'));

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/facebook/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testExchangeCodeWithoutPkceVerifier(): void
    {
        $code = $this->faker->sha256();
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->facebook->expects($this->never())->method('setPkceCode');
        $this->facebook->method('getAccessToken')->willReturn($accessToken);

        $this->assertSame(
            $expectedToken,
            $this->provider->exchangeCode($code, null),
        );
    }

    public function testExchangeCodeDoesNotReusePkceVerifierAcrossCalls(): void
    {
        $facebook = new StatefulFacebookProviderDouble();
        $provider = new FacebookOAuthProvider(
            $facebook,
            OAuthProvider::fromString('facebook'),
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

    public function testFetchProfileUsesEmptyStringWhenNameIsNull(): void
    {
        $email = $this->faker->safeEmail();

        $owner = $this->createMock(FacebookUser::class);
        $owner->method('getEmail')->willReturn($email);
        $owner->method('getName')->willReturn(null);
        $owner->method('getId')->willReturn($this->faker->uuid());

        $this->facebook->method('getResourceOwner')->willReturn($owner);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertSame('', $profile->name);
    }
}

final class StatefulFacebookProviderDouble extends Facebook
{
    public function __construct()
    {
    }

    #[\Override]
    public function setPkceCode($pkceCode)
    {
        parent::setPkceCode($pkceCode);

        return $this;
    }

    #[\Override]
    public function getAccessToken($grant = 'authorization_code', array $params = []): AccessToken
    {
        return new AccessToken([
            'access_token' => sprintf(
                '%s|%s',
                $params['code'],
                $this->getPkceCode() ?? 'none',
            ),
        ]);
    }
}
