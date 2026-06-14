<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverSignInLimitersTest extends RateLimitClientTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testResolveEndpointLimitersForPasswordResetRequest(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/reset-password',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('password_reset_ip', $byName);
        self::assertSame('ip:' . $clientIp, $byName['password_reset_ip']);
    }

    public function testResolveEndpointLimitersSkipsPasswordResetIpForConfirmPath(): void
    {
        $request = Request::create('/api/reset-password/confirm', 'POST', [], [], [], [
            'REMOTE_ADDR' => $this->faker->ipv4(),
        ]);

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('password_reset_ip', $names);
        self::assertContains('password_reset_confirm', $names);
    }

    public function testResolveEndpointLimitersSkipsPasswordResetIpForGetMethod(): void
    {
        $request = Request::create('/api/reset-password', 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('password_reset_ip', $names);
    }

    public function testResolveEndpointLimitersForSignIn(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create('/api/signin', 'POST', [], [], [], ['REMOTE_ADDR' => $clientIp]);

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('signin_ip', $byName);
        self::assertSame('ip:' . $clientIp, $byName['signin_ip']);
    }

    public function testResolveEndpointLimitersForSignInWithEmailInBody(): void
    {
        $email = $this->faker->email();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email], JSON_THROW_ON_ERROR)
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('signin_email', $byName);
        self::assertSame('email:' . strtolower(trim($email)), $byName['signin_email']);
    }

    public function testResolveEndpointLimitersForSignInTwoFactor(): void
    {
        $request = Request::create('/api/signin/2fa', 'POST');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

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

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertContains('registration', $names);
    }

    public function testResolveEndpointLimitersForCollectionWithFormatExtension(): void
    {
        $request = Request::create('/api/users.jsonld', 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertContains('user_collection', $names);
    }

    public function testResolveEndpointLimitersForGraphQlSignInMutation(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $clientIp = $this->faker->ipv4();
        $request = $this->graphQlRequest($clientIp, [
            'query' => 'mutation SignIn($input: SignInInput!) { signIn(input: $input) { id } }',
            'variables' => ['input' => ['email' => $email, 'password' => $password]],
        ]);

        $byName = array_column(
            $this->resolver->resolveEndpointLimiters($request),
            'key',
            'name'
        );

        self::assertSame('ip:' . $clientIp, $byName['signin_ip']);
        self::assertSame('email:' . strtolower(trim($email)), $byName['signin_email']);
    }

    public function testResolveEndpointLimitersForGraphQlRefreshTokenMutation(): void
    {
        $clientIp = $this->faker->ipv4();
        $refreshToken = $this->faker->sha256();
        $request = $this->graphQlRequest($clientIp, [
            'query' => 'mutation { refreshToken(input: {refreshToken: "'
                . $refreshToken . '"}) { user { id } } }',
        ]);

        $byName = array_column(
            $this->resolver->resolveEndpointLimiters($request),
            'key',
            'name'
        );

        self::assertSame('ip:' . $clientIp, $byName['refresh_token']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function graphQlRequest(string $clientIp, array $payload): Request
    {
        return Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }
}
