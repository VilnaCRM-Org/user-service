<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverLimitersTest extends RateLimitClientTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testResolveGlobalLimiterReturnsAnonymousWhenNoAuth(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createRequestWithIp('/api/users', 'GET', $clientIp);

        $result = $this->resolver->resolveGlobalLimiter($request);

        self::assertSame('global_api_anonymous', $result['name']);
        self::assertSame('ip:' . $clientIp, $result['key']);
    }

    public function testResolveGlobalLimiterReturnsAuthenticatedForValidJwt(): void
    {
        $now = time();
        $jwtConverter = $this->createMock(JwtTokenConverterInterface::class);
        $jwtConverter->method('decode')->willReturn([
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $this->faker->uuid(),
            'nbf' => $now - 10,
            'exp' => $now + 900,
        ]);

        $clientIp = $this->faker->ipv4();
        $resolver = $this->createRequestResolver($jwtConverter);
        $request = $this->createRequestWithIp('/api/users', 'GET', $clientIp);
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        $result = $resolver->resolveGlobalLimiter($request);

        self::assertSame('global_api_authenticated', $result['name']);
        self::assertSame('ip:' . $clientIp, $result['key']);
    }

    public function testResolveEndpointLimitersForRegistrationPost(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createRequestWithIp('/api/users', 'POST', $clientIp);

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('registration', $byName);
        self::assertSame('ip:' . $clientIp, $byName['registration']);
    }

    public function testResolveEndpointLimitersForUserCollectionGet(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createRequestWithIp('/api/users', 'GET', $clientIp);

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('user_collection', $byName);
        self::assertSame('ip:' . $clientIp, $byName['user_collection']);
    }

    public function testResolveEndpointLimitersForOauthTokenPath(): void
    {
        $request = $this->createRequestWithIp('/api/token', 'POST', '127.0.0.1');

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('refresh_token', $byName);
        self::assertSame('ip:127.0.0.1', $byName['refresh_token']);
    }

    public function testResolveEndpointLimitersForOauthAlternatePath(): void
    {
        $request = $this->createRequestWithIp('/api/oauth/token', 'POST', '127.0.0.1');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertContains('oauth_token', $names);
    }

    public function testResolveEndpointLimitersOauthTokenUsesClientIdFromBasicAuth(): void
    {
        $clientId = $this->faker->lexify('client???');
        $request = $this->createRequestWithIp('/api/token', 'POST', '127.0.0.1');
        $request->headers->set(
            'Authorization',
            'Basic ' . base64_encode($clientId . ':' . $this->faker->password())
        );

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertSame('ip:127.0.0.1', $byName['refresh_token']);
    }

    public function testResolveEndpointLimitersOauthAlternatePathUsesClientIdFromBasicAuth(): void
    {
        $clientId = $this->faker->lexify('client???');
        $request = $this->createRequestWithIp('/api/oauth/token', 'POST', '127.0.0.1');
        $request->headers->set(
            'Authorization',
            'Basic ' . base64_encode($clientId . ':' . $this->faker->password())
        );

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertSame('client:' . $clientId, $byName['oauth_token']);
    }

    public function testResolveEndpointLimitersForEmailConfirmation(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createRequestWithIp('/api/users/confirm', 'PATCH', $clientIp);

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('email_confirmation', $byName);
        self::assertSame('ip:' . $clientIp, $byName['email_confirmation']);
    }

    public function testResolveEndpointLimitersForPasswordResetRequest(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createRequestWithIp('/api/reset-password', 'POST', $clientIp);

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('password_reset_ip', $byName);
        self::assertSame('ip:' . $clientIp, $byName['password_reset_ip']);
    }

    public function testResolveEndpointLimitersSkipsPasswordResetIpForConfirmPath(): void
    {
        $request = $this->createRequestWithIp('/api/reset-password/confirm', 'POST', '127.0.0.1');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertNotContains('password_reset_ip', $names);
        self::assertContains('password_reset_confirm', $names);
    }

    public function testResolveEndpointLimitersSkipsPasswordResetIpForGetMethod(): void
    {
        $request = Request::create('/api/reset-password', 'GET');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertNotContains('password_reset_ip', $names);
    }

    public function testResolveEndpointLimitersForSignIn(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createRequestWithIp('/api/signin', 'POST', $clientIp);

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('signin_ip', $byName);
        self::assertSame('ip:' . $clientIp, $byName['signin_ip']);
    }

    public function testResolveEndpointLimitersForSignInWithEmailInBody(): void
    {
        $email = $this->faker->email();
        $request = $this->createJsonPostRequest(
            '/api/signin',
            json_encode(['email' => $email], JSON_THROW_ON_ERROR)
        );

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('signin_email', $byName);
        self::assertSame('email:' . strtolower(trim($email)), $byName['signin_email']);
    }

    public function testResolveEndpointLimitersForSignInTwoFactor(): void
    {
        $request = Request::create('/api/signin/2fa', 'POST');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertContains('twofa_verification_ip', $names);
    }

    public function testResolveEndpointLimitersReturnsEmptyForUnrecognizedApiPath(): void
    {
        $request = Request::create('/api/health', 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame([], $limiters);
    }

    public function testResolveEndpointLimitersForRegistrationWithFormatExtension(): void
    {
        $request = Request::create('/api/users.json', 'POST');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertContains('registration', $names);
    }

    public function testResolveEndpointLimitersForCollectionWithFormatExtension(): void
    {
        $request = Request::create('/api/users.jsonld', 'GET');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertContains('user_collection', $names);
    }

    public function testResolveEndpointLimitersForGraphQlSignInMutation(): void
    {
        $email = $this->faker->email();
        $request = $this->createGraphQlRequest(
            'mutation { signIn(input: $input) { id } }',
            ['input' => ['email' => $email, 'password' => $this->faker->password()]],
            '203.0.113.11'
        );

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertSame('ip:203.0.113.11', $byName['signin_ip']);
        self::assertSame('email:' . strtolower(trim($email)), $byName['signin_email']);
    }

    public function testResolveEndpointLimitersForGraphQlRefreshTokenMutation(): void
    {
        $refreshToken = $this->faker->sha256();
        $request = $this->createGraphQlRequest(
            'mutation { refreshToken(input: {refreshToken: "' . $refreshToken . '"}) { user { id } } }',
            [],
            '203.0.113.12'
        );

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertSame('ip:203.0.113.12', $byName['refresh_token']);
    }
}
