<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverExtendedLimitersTest extends UnitTestCase
{
    public function testResolveEndpointLimitersForPasswordResetConfirmPath(): void
    {
        $resolver = new ApiRateLimitRequestResolver();
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
        $resolver = new ApiRateLimitRequestResolver();
        $request = Request::create('/api/signout', 'POST');

        $limiters = $resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('signout', $names);
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

        return new ApiRateLimitRequestResolver(
            new ApiRateLimitClientIdentityResolver($jwtConverter)
        );
    }
}
