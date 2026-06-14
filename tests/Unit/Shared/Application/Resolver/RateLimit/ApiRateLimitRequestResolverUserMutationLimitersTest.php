<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverUserMutationLimitersTest extends RateLimitClientTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testResolveEndpointLimitersForUserUpdatePatch(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'PATCH');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('user_update', $byName);
        self::assertSame('user:' . $userId, $byName['user_update']);
    }

    public function testResolveEndpointLimitersForUserUpdatePut(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'PUT');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('user_update', $byName);
        self::assertSame('user:' . $userId, $byName['user_update']);
    }

    public function testResolveEndpointLimitersForUserDelete(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'DELETE');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('user_delete', $byName);
        self::assertSame('user:' . $userId, $byName['user_delete']);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForBatchPath(): void
    {
        $request = Request::create('/api/users/batch', 'PATCH');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('user_update', $names);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForConfirmPath(): void
    {
        $request = Request::create('/api/users/confirm', 'PATCH');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('user_update', $names);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForGetRequest(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('user_update', $names);
        self::assertNotContains('user_delete', $names);
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
}
