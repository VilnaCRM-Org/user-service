<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitAuthTargetResolverTwoFactorEndpointsTest extends
    ApiRateLimitAuthTargetResolverTestCase
{
    private const COMPLETE_TWO_FACTOR_MUTATION = <<<'GRAPHQL'
        mutation completeTwoFactor($input: completeTwoFactorInput!) {
            completeTwoFactor(input: $input) { authPayload { accessToken } }
        }
        GRAPHQL;

    public function testResolveReturnsTwoFaSetupLimiterWhenUserAuthenticated(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = $this->createClientIdentityResolver($this->jwtConverter);
        $resolver = $this->createAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/2fa/setup', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_setup', $result[0]['name']);
        self::assertSame('user:' . $subject, $result[0]['key']);
    }

    public function testResolveReturnsTwoFaConfirmLimiterWhenUserAuthenticated(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = $this->createClientIdentityResolver($this->jwtConverter);
        $resolver = $this->createAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/2fa/confirm', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_confirm', $result[0]['name']);
        self::assertSame('user:' . $subject, $result[0]['key']);
    }

    public function testResolveReturnsTwoFaDisableLimiterWhenUserAuthenticated(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = $this->createClientIdentityResolver($this->jwtConverter);
        $resolver = $this->createAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/2fa/disable', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_disable', $result[0]['name']);
        self::assertSame('user:' . $subject, $result[0]['key']);
    }

    public function testResolveReturnsEmptyForTwoFaSetupWhenNotAuthenticated(): void
    {
        $resolver = $this->createAuthTargetResolver();
        $request = Request::create('/api/2fa/setup', 'POST');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForTwoFaConfirmWhenNotAuthenticated(): void
    {
        $resolver = $this->createAuthTargetResolver();
        $request = Request::create('/api/2fa/confirm', 'POST');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForTwoFaDisableWhenNotAuthenticated(): void
    {
        $resolver = $this->createAuthTargetResolver();
        $request = Request::create('/api/2fa/disable', 'POST');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForTwoFaSetupWithGetMethod(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = $this->createClientIdentityResolver($this->jwtConverter);
        $resolver = $this->createAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/2fa/setup', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForUnknownTwoFaPath(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = $this->createClientIdentityResolver($this->jwtConverter);
        $resolver = $this->createAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/2fa/unknown', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveAppliesTwoFactorIpLimiterToGraphQlCompleteTwoFactor(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = $this->createAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = $this->createGraphQlCompleteTwoFactorRequest($clientIp, null);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_verification_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
    }

    public function testResolveAppliesBothLimitersToGraphQlCompleteTwoFactor(): void
    {
        $clientIp = $this->faker->ipv4();
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $this->stubPendingSession($sessionId, $userId);

        $resolver = $this->createAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = $this->createGraphQlCompleteTwoFactorRequest($clientIp, $sessionId);

        $result = $resolver->resolve($request);

        self::assertCount(2, $result);
        self::assertSame('twofa_verification_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
        self::assertSame('twofa_verification_user', $result[1]['name']);
        self::assertSame('user:' . $userId, $result[1]['key']);
    }

    public function testResolveIgnoresUnrelatedGraphQlMutation(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = $this->createAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['query' => 'mutation { createUser(input: {}) { user { id } } }'],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame([], $resolver->resolve($request));
    }

    private function createGraphQlCompleteTwoFactorRequest(
        string $clientIp,
        ?string $sessionId
    ): Request {
        $body = ['query' => self::COMPLETE_TWO_FACTOR_MUTATION];

        if ($sessionId !== null) {
            $body['variables'] = [
                'input' => [
                    'pendingSessionId' => $sessionId,
                    'twoFactorCode' => '123456',
                ],
            ];
        }

        return Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }
}
