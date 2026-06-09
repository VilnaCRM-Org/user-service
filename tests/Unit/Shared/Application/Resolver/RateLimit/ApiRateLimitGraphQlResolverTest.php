<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlResolver;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitGraphQlResolverTest extends RateLimitClientTestCase
{
    private const ENDPOINT = '/api/graphql';

    /**
     * @param array<string, mixed> $variables
     */
    private function createGraphQlRequest(
        string $query,
        array $variables = [],
        string $clientIp = '203.0.113.7'
    ): Request {
        $body = ['query' => $query];
        if ($variables !== []) {
            $body['variables'] = $variables;
        }

        return Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }

    public function testSignInMutationProducesIpAndEmailLimiters(): void
    {
        $email = $this->faker->email();
        $request = $this->createGraphQlRequest(
            'mutation($input: signInInput!) { signIn(input: $input) { user { id } } }',
            ['input' => ['email' => $email, 'password' => 'secret']],
            '198.51.100.5'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('ip:198.51.100.5', $byName['signin_ip']);
        self::assertSame('email:' . strtolower(trim($email)), $byName['signin_email']);
    }

    public function testSignInMutationWithoutEmailStillThrottlesByIp(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { signIn(input: {password: "x"}) { user { id } } }',
            [],
            '198.51.100.9'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('ip:198.51.100.9', $byName['signin_ip']);
        self::assertArrayNotHasKey('signin_email', $byName);
    }

    public function testRefreshTokenMutationProducesRefreshLimiter(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($input: refreshTokenInput!) { refreshToken(input: $input) { user { id } } }',
            ['input' => ['refreshToken' => $this->faker->sha256()]],
            '198.51.100.20'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('ip:198.51.100.20', $byName['refresh_token']);
    }

    public function testConfirmPasswordResetMutationProducesConfirmLimiter(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { confirmPasswordReset(input: {token: "t", newPassword: "p"}) { id } }',
            [],
            '198.51.100.30'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('ip:198.51.100.30', $byName['password_reset_confirm']);
    }

    public function testCompleteTwoFactorMutationProducesIpAndUserLimiters(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $repository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $repository->method('findById')->with($sessionId)->willReturn(
            new PendingTwoFactor($sessionId, $userId, new DateTimeImmutable())
        );

        $request = $this->createGraphQlRequest(
            'mutation($input: completeTwoFactorInput!) '
            . '{ completeTwoFactor(input: $input) { user { id } } }',
            ['input' => ['pendingSessionId' => $sessionId, 'twoFactorCode' => '123456']],
            '198.51.100.40'
        );

        $resolver = new ApiRateLimitGraphQlResolver(
            $this->createJsonSerializer(),
            $this->createClientIdentityResolver(),
            $repository
        );
        $byName = array_column($resolver->resolve($request), 'key', 'name');

        self::assertSame('ip:198.51.100.40', $byName['twofa_verification_ip']);
        self::assertSame('user:' . $userId, $byName['twofa_verification_user']);
    }

    public function testCompleteTwoFactorWithoutPendingSessionOnlyThrottlesByIp(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { completeTwoFactor(input: {twoFactorCode: "123456"}) { user { id } } }'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertArrayHasKey('twofa_verification_ip', $byName);
        self::assertArrayNotHasKey('twofa_verification_user', $byName);
    }

    public function testSignOutMutationProducesUserLimiterForAuthenticatedRequest(): void
    {
        $userId = $this->faker->uuid();
        $jwtConverter = $this->createMock(
            \App\Shared\Application\Converter\JwtTokenConverterInterface::class
        );
        $jwtConverter->method('decode')->willReturn($this->buildValidPayload(['sub' => $userId]));

        $request = $this->createGraphQlRequest('mutation { signOut(input: {}) { id } }');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        $resolver = $this->createGraphQlResolver(
            $this->createClientIdentityResolver($jwtConverter)
        );
        $byName = array_column($resolver->resolve($request), 'key', 'name');

        self::assertSame('user:' . $userId, $byName['signout']);
    }

    public function testSignOutAllMutationSkippedWhenUnauthenticated(): void
    {
        $request = $this->createGraphQlRequest('mutation { signOutAll(input: {}) { id } }');

        $names = array_column($this->createGraphQlResolver()->resolve($request), 'name');

        self::assertNotContains('signout_all', $names);
    }

    public function testNonGraphQlPathReturnsNoTargets(): void
    {
        $request = Request::create('/api/users', 'POST');

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testGraphQlGetRequestReturnsNoTargets(): void
    {
        $request = Request::create(self::ENDPOINT, 'GET');

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNonSensitiveMutationReturnsNoTargets(): void
    {
        $request = $this->createGraphQlRequest('mutation { createUser(input: {}) { id } }');

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testInvalidJsonBodyReturnsNoTargets(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-json'
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testMissingQueryFieldReturnsNoTargets(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['variables' => ['input' => []]], JSON_THROW_ON_ERROR)
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testMultipleSensitiveMutationsAreAllThrottled(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { refreshToken(input: {refreshToken: "x"}) { user { id } } '
            . 'confirmPasswordReset(input: {token: "t", newPassword: "p"}) { id } }'
        );

        $names = array_column($this->createGraphQlResolver()->resolve($request), 'name');

        self::assertContains('refresh_token', $names);
        self::assertContains('password_reset_confirm', $names);
    }
}
