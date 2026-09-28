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

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('user_update', $byName);
        self::assertSame('user:' . $userId, $byName['user_update']);
    }

    public function testResolveEndpointLimitersForUserUpdatePut(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'PUT');

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('user_update', $byName);
        self::assertSame('user:' . $userId, $byName['user_update']);
    }

    public function testResolveEndpointLimitersForUserDelete(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'DELETE');

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('user_delete', $byName);
        self::assertSame('user:' . $userId, $byName['user_delete']);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForBatchPath(): void
    {
        $request = Request::create('/api/users/batch', 'PATCH');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertNotContains('user_update', $names);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForConfirmPath(): void
    {
        $request = Request::create('/api/users/confirm', 'PATCH');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertNotContains('user_update', $names);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForGetRequest(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'GET');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertNotContains('user_update', $names);
        self::assertNotContains('user_delete', $names);
    }

    public function testResolveEndpointLimitersForResendConfirmationEmail(): void
    {
        $userId = $this->faker->uuid();
        $clientIp = $this->faker->ipv4();
        $request = $this->createRequestWithIp(
            '/api/users/' . $userId . '/resend-confirmation-email',
            'POST',
            $clientIp
        );

        $byName = $this->resolveEndpointLimiterKeysByName($this->resolver, $request);

        self::assertArrayHasKey('resend_confirmation', $byName);
        self::assertArrayHasKey('resend_confirmation_target', $byName);
        self::assertSame('ip:' . $clientIp, $byName['resend_confirmation']);
        self::assertSame('user:' . $userId, $byName['resend_confirmation_target']);
    }

    public function testResolveEndpointLimitersSkipsResendConfirmationForGetMethod(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId . '/resend-confirmation-email', 'GET');

        $names = $this->resolveEndpointLimiterNames($this->resolver, $request);

        self::assertNotContains('resend_confirmation', $names);
    }
}
