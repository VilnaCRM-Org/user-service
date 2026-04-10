<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Provider;

use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\OAuth\Infrastructure\Provider\GitHubOAuthProvider;
use App\Tests\Unit\UnitTestCase;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

final class GitHubOAuthProviderTest extends UnitTestCase
{
    private Github $github;
    private GitHubOAuthProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->github = $this->createMock(Github::class);
        $this->provider = new GitHubOAuthProvider(
            $this->github,
            OAuthProvider::fromString('github'),
        );
    }

    public function testGetProviderReturnsGitHub(): void
    {
        $this->assertSame('github', (string) $this->provider->getProvider());
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

    public function testGetAuthorizationUrlIncludesPkceParams(): void
    {
        $state = $this->faker->uuid();
        $codeChallenge = $this->faker->sha256();
        $expectedUrl = 'https://github.com/login/oauth/authorize?test=1';

        $this->github->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state, $codeChallenge): bool {
                    return $options['state'] === $state
                        && $options['code_challenge'] === $codeChallenge
                        && $options['code_challenge_method'] === 'S256'
                        && $options['scope'] === ['user:email'];
                }
            ))
            ->willReturn($expectedUrl);

        $url = $this->provider->getAuthorizationUrl($state, $codeChallenge);

        $this->assertSame($expectedUrl, $url);
    }

    public function testGetAuthorizationUrlWithoutPkce(): void
    {
        $state = $this->faker->uuid();

        $this->github->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(
                static function (array $options) use ($state): bool {
                    return $options['state'] === $state
                        && !isset($options['code_challenge'])
                        && !isset($options['code_challenge_method']);
                }
            ))
            ->willReturn('https://github.com/login/oauth/authorize');

        $this->provider->getAuthorizationUrl($state, null);
    }

    public function testExchangeCodeReturnsAccessToken(): void
    {
        $code = $this->faker->sha256();
        $codeVerifier = $this->faker->sha256();
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->github->expects($this->once())
            ->method('setPkceCode')
            ->with($codeVerifier);

        $this->github->expects($this->once())
            ->method('getAccessToken')
            ->with('authorization_code', ['code' => $code])
            ->willReturn($accessToken);

        $this->assertSame($expectedToken, $this->provider->exchangeCode($code, $codeVerifier));
    }

    public function testExchangeCodeThrowsOAuthProviderExceptionOnFailure(): void
    {
        $this->github->method('getAccessToken')
            ->willThrowException(new IdentityProviderException('bad_code', 400, []));

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/github/');

        $this->provider->exchangeCode('invalid_code', null);
    }

    public function testFetchProfileReturnsProfileWithVerifiedEmail(): void
    {
        $email = $this->faker->safeEmail();
        $name = $this->faker->name();
        $nickname = $this->faker->userName();
        $id = $this->faker->randomNumber(5);

        $this->stubResourceOwner($name, $nickname, $id);
        $this->stubEmailsEndpoint([
            ['email' => $email, 'primary' => true, 'verified' => true],
        ]);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertInstanceOf(OAuthUserProfile::class, $profile);
        $this->assertSame($email, $profile->email);
        $this->assertSame($name, $profile->name);
        $this->assertSame((string) $id, $profile->providerId);
        $this->assertTrue($profile->emailVerified);
    }

    public function testFetchProfileUsesNicknameWhenNameIsNull(): void
    {
        $nickname = $this->faker->userName();

        $this->stubResourceOwner(null, $nickname, 123);
        $this->stubEmailsEndpoint([
            ['email' => $this->faker->safeEmail(), 'primary' => true, 'verified' => true],
        ]);

        $profile = $this->provider->fetchProfile($this->faker->sha256());

        $this->assertSame($nickname, $profile->name);
    }

    public function testFetchProfileThrowsUnverifiedWhenNoVerifiedPrimaryEmail(): void
    {
        $this->stubResourceOwner('Test', null, 1);
        $this->stubEmailsEndpoint([
            ['email' => $this->faker->safeEmail(), 'primary' => true, 'verified' => false],
        ]);

        $this->expectException(UnverifiedProviderEmailException::class);
        $this->expectExceptionMessageMatches('/github/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testFetchProfileThrowsUnverifiedWhenNoEmails(): void
    {
        $this->stubResourceOwner('Test', null, 1);
        $this->stubEmailsEndpoint([]);

        $this->expectException(UnverifiedProviderEmailException::class);

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testFetchProfileThrowsOAuthProviderExceptionOnApiError(): void
    {
        $this->github->method('getResourceOwner')
            ->willThrowException(new \RuntimeException('API unavailable'));

        $this->expectException(OAuthProviderException::class);
        $this->expectExceptionMessageMatches('/github/');

        $this->provider->fetchProfile($this->faker->sha256());
    }

    public function testExchangeCodeWithoutPkceVerifier(): void
    {
        $expectedToken = $this->faker->sha256();

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')->willReturn($expectedToken);

        $this->github->expects($this->never())->method('setPkceCode');
        $this->github->method('getAccessToken')->willReturn($accessToken);

        $this->assertSame($expectedToken, $this->provider->exchangeCode('code', null));
    }

    private function stubResourceOwner(
        ?string $name,
        ?string $nickname,
        int $id,
    ): void {
        $owner = $this->createMock(GithubResourceOwner::class);
        $owner->method('getName')->willReturn($name);
        $owner->method('getNickname')->willReturn($nickname);
        $owner->method('getId')->willReturn($id);

        $this->github->method('getResourceOwner')->willReturn($owner);
    }

    /**
     * @param list<array{email: string, primary: bool, verified: bool}> $emails
     */
    private function stubEmailsEndpoint(array $emails): void
    {
        $request = $this->createMock(RequestInterface::class);
        $this->github->method('getAuthenticatedRequest')->willReturn($request);
        $this->github->method('getParsedResponse')->willReturn($emails);
    }
}
