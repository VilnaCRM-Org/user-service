<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverExtendedLimitersTest extends RateLimitClientTestCase
{
    public function testResolveEndpointLimitersForPasswordResetConfirmPath(): void
    {
        $resolver = $this->createRequestResolver();
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/reset-password/confirm',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('password_reset_confirm', $byName);
        self::assertSame('ip:' . $clientIp, $byName['password_reset_confirm']);
    }

    public function testResolveEndpointLimitersForRecoveryCodesWhenAuthenticated(): void
    {
        $subject = $this->faker->uuid();
        $resolver = $this->createResolverWithAuthenticatedSubject($subject);
        $request = Request::create('/api/2fa/recovery-codes', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        $limiters = $resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('recovery_codes', $byName);
        self::assertSame('user:' . $subject, $byName['recovery_codes']);
    }

    public function testResolveEndpointLimitersForSignoutWhenAuthenticated(): void
    {
        $subject = $this->faker->uuid();
        $resolver = $this->createResolverWithAuthenticatedSubject($subject);
        $request = Request::create('/api/signout', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        $limiters = $resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('signout', $byName);
        self::assertSame('user:' . $subject, $byName['signout']);
    }

    public function testResolveEndpointLimitersForSignoutAllWhenAuthenticated(): void
    {
        $subject = $this->faker->uuid();
        $resolver = $this->createResolverWithAuthenticatedSubject($subject);
        $request = Request::create('/api/signout/all', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        $limiters = $resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('signout_all', $byName);
        self::assertSame('user:' . $subject, $byName['signout_all']);
    }

    public function testResolveEndpointLimitersSkipsSignoutWhenNotAuthenticated(): void
    {
        $resolver = $this->createRequestResolver();
        $request = Request::create('/api/signout', 'POST');

        $limiters = $resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('signout', $names);
    }

    public function testResolveEndpointLimitersForOAuthSocialInitiate(): void
    {
        $resolver = $this->createRequestResolver();
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/auth/social/github',
            'GET',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('oauth_social_initiate', $byName);
        self::assertSame('ip:' . $clientIp, $byName['oauth_social_initiate']);
    }

    public function testResolveEndpointLimitersForOAuthSocialCallback(): void
    {
        $resolver = $this->createRequestResolver();
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/auth/social/github/callback',
            'GET',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('oauth_social_callback', $byName);
        self::assertSame('ip:' . $clientIp, $byName['oauth_social_callback']);
    }

    public function testOAuthSocialInitiateNotMatchedForPostMethod(): void
    {
        $resolver = $this->createRequestResolver();
        $request = Request::create('/api/auth/social/github', 'POST');

        $limiters = $resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('oauth_social_initiate', $names);
    }

    public function testOAuthSocialCallbackNotMatchedForPostMethod(): void
    {
        $resolver = $this->createRequestResolver();
        $request = Request::create(
            '/api/auth/social/github/callback',
            'POST'
        );

        $limiters = $resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('oauth_social_callback', $names);
    }

    public function testCallbackPathDoesNotMatchInitiateLimiter(): void
    {
        $resolver = $this->createRequestResolver();
        $request = Request::create(
            '/api/auth/social/github/callback',
            'GET'
        );

        $limiters = $resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('oauth_social_initiate', $names);
        self::assertContains('oauth_social_callback', $names);
    }

    private function createResolverWithAuthenticatedSubject(
        string $subject
    ): ApiRateLimitRequestResolver {
        $now = time();
        $jwtConverter = $this->createMock(JwtTokenConverterInterface::class);
        $jwtConverter->method('decode')->willReturn([
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $subject,
            'nbf' => $now - 10,
            'exp' => $now + 900,
        ]);

        return $this->createRequestResolver($jwtConverter);
    }
}
