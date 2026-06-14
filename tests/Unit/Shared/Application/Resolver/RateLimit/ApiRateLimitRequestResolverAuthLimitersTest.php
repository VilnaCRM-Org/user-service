<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverAuthLimitersTest extends RateLimitClientTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testResolveEndpointLimitersForResendConfirmationEmail(): void
    {
        $userId = $this->faker->uuid();
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/users/' . $userId . '/resend-confirmation-email',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('resend_confirmation', $byName);
        self::assertArrayHasKey('resend_confirmation_target', $byName);
        self::assertSame('ip:' . $clientIp, $byName['resend_confirmation']);
        self::assertSame('user:' . $userId, $byName['resend_confirmation_target']);
    }

    public function testResolveEndpointLimitersSkipsResendConfirmationForGetMethod(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId . '/resend-confirmation-email', 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('resend_confirmation', $names);
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
            'REMOTE_ADDR' => '127.0.0.1',
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

    public function testResolveEndpointLimitersForGraphQlSignInMutation(): void
    {
        $email = $this->faker->email();
        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '203.0.113.11', 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'query' => 'mutation { signIn(input: $input) { id } }',
                'variables' => ['input' => ['email' => $email, 'password' => 'secret']],
            ], JSON_THROW_ON_ERROR)
        );

        $byName = array_column(
            $this->resolver->resolveEndpointLimiters($request),
            'key',
            'name'
        );

        self::assertSame('ip:203.0.113.11', $byName['signin_ip']);
        self::assertSame('email:' . strtolower(trim($email)), $byName['signin_email']);
    }

    public function testResolveEndpointLimitersForGraphQlRefreshTokenMutation(): void
    {
        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '203.0.113.12', 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'query' => 'mutation { refreshToken(input: {refreshToken: "x"}) { user { id } } }',
            ], JSON_THROW_ON_ERROR)
        );

        $byName = array_column(
            $this->resolver->resolveEndpointLimiters($request),
            'key',
            'name'
        );

        self::assertSame('ip:203.0.113.12', $byName['refresh_token']);
    }
}
