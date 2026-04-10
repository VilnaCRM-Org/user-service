<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\OAuth\Infrastructure\Provider\GoogleOAuthProvider;
use App\Tests\Unit\UnitTestCase;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

final class GoogleOAuthProviderTest extends UnitTestCase
{
    private Google $google;
    private GoogleOAuthProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->google = $this->createMock(Google::class);
        $this->provider = new GoogleOAuthProvider(
            $this->google,
            OAuthProvider::fromString('google'),
        );
    }

    public function testGetProviderReturnsGoogle(): void
    {
        $this->assertSame('google', (string) $this->provider->getProvider());
    }

    public function testSupportsPkceReturnsTrue(): void
    {
        $this->assertTrue($this->provider->supportsPkce());
    }

    public function testEmailAlwaysVerifiedReturnsTrue(): void
    {
        $this->assertTrue($this->provider->emailAlwaysVerified());
    }

    public function testRequiresExtraProfileCallReturnsFalse(): void
    {
        $this->assertFalse($this->provider->requiresExtraProfileCall());
    }

    public function testGetAuthorizationUrlIncludesPkceAndScopes(): void
    {
        $state = $this->faker->uuid();
        $codeChallenge = $this->faker->sha256();

        $expectedUrl = 'https://accounts.google.com/o/oauth2/v2/auth?test=1';

        $this->google->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state, $codeChallenge): bool {
                    return $options['state'] === $state
                        && $options['code_challenge'] === $codeChallenge
                        && $options['code_challenge_method'] === 'S256'
                        && $options['scope'] === ['openid', 'email', 'profile'];
                }
            ))
            ->willReturn($expectedUrl);

        $url = $this->provider->getAuthorizationUrl($state, $codeChallenge);

        $this->assertSame($expectedUrl, $url);
    }

    public function testGetAuthorizationUrlWithoutPkce(): void
    {
        $state = $this->faker->uuid();

        $this->google->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state): bool {
                    return $options['state'] === $state
                        && !isset($options['code_challenge']);
                }
            ))
            ->willReturn('https://accounts.google.com/o/oauth2/v2/auth');

        $this->provider->getAuthorizationUrl($state, null);
    }

    public function testExchangeCodeReturnsAccessToken(): void
    {
        $code = $this->faker->sha256();
        $codeVerifier = $this->faker->sha256();
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->google->expects($this->once())
            ->method('setPkceCode')
            ->with($codeVerifier);

        $this->google->expects($this->once())
            ->method('getAccessToken')
            ->with('authorization_code', ['code' => $code])
            ->willReturn($accessToken);

        $token = $this->provider->exchangeCode($code, $codeVerifier);

        $this->assertSame($expectedToken, $token);
    }

    public function testExchangeCodeThrowsOAuthProviderExceptionOnFailure(): void
    {
        $this->google->method('getAccessToken')
            ->willThrowException(
                new IdentityProviderException('invalid_grant', 400, [])
            );

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/google/');

        $this->provider->exchangeCode('invalid_code', null);
    }

    public function testExchangeCodeThrowsOAuthProviderExceptionOnUnexpectedFailure(): void
    {
        $exception = new \RuntimeException('transport error');
        $this->google->method('getAccessToken')->willThrowException($exception);

        try {
            $this->provider->exchangeCode($this->faker->sha256(), null);
            $this->fail('Expected OAuthProviderException to be thrown.');
        } catch (OAuthProviderException $caught) {
            $this->assertSame($exception, $caught->getPrevious());
            $this->assertStringContainsString('transport error', $caught->getMessage());
        }
    }

    public function testFetchProfileReturnsProfileWithVerifiedEmail(): void
    {
        $email = $this->faker->safeEmail();
        $name = $this->faker->name();
        $id = $this->faker->uuid();

        $owner = $this->createMock(GoogleUser::class);
        $owner->method('getEmail')->willReturn($email);
        $owner->method('getEmailVerified')->willReturn(true);
        $owner->method('getName')->willReturn($name);
        $owner->method('getId')->willReturn($id);

        $this->google->method('getResourceOwner')->willReturn($owner);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertInstanceOf(OAuthUserProfile::class, $profile);
        $this->assertSame($email, $profile->email);
        $this->assertSame($name, $profile->name);
        $this->assertSame($id, $profile->providerId);
        $this->assertTrue($profile->emailVerified);
    }

    public function testFetchProfileThrowsUnverifiedWhenEmailNotVerified(): void
    {
        $owner = $this->createMock(GoogleUser::class);
        $owner->method('getEmail')->willReturn($this->faker->safeEmail());
        $owner->method('getEmailVerified')->willReturn(false);

        $this->google->method('getResourceOwner')->willReturn($owner);

        $this->expectException(UnverifiedProviderEmailException::class);
        $this->expectExceptionMessageMatches('/google/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testFetchProfileThrowsUnverifiedWhenEmailIsNull(): void
    {
        $owner = $this->createMock(GoogleUser::class);
        $owner->method('getEmail')->willReturn(null);
        $owner->method('getEmailVerified')->willReturn(null);

        $this->google->method('getResourceOwner')->willReturn($owner);

        $this->expectException(UnverifiedProviderEmailException::class);

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testFetchProfileThrowsOAuthProviderExceptionOnApiError(): void
    {
        $this->google->method('getResourceOwner')
            ->willThrowException(new \RuntimeException('API unavailable'));

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/google/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testExchangeCodeWithoutPkceVerifier(): void
    {
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->google->expects($this->never())->method('setPkceCode');
        $this->google->method('getAccessToken')->willReturn($accessToken);

        $token = $this->provider->exchangeCode('code', null);

        $this->assertSame($expectedToken, $token);
    }
}
