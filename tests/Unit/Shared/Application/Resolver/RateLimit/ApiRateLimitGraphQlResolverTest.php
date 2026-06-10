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
            'mutation { completeTwoFactor(input: $input) { id } }',
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
            'mutation { refreshToken(input: {}) { id } confirmPasswordReset(input: {}) { id } }'
        );

        $names = array_column($this->createGraphQlResolver()->resolve($request), 'name');

        self::assertContains('refresh_token', $names);
        self::assertContains('password_reset_confirm', $names);
    }

    public function testScalarJsonBodyReturnsNoTargets(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '"signIn"'
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNullJsonBodyReturnsNoTargets(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'null'
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNonArrayVariablesAreIgnored(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['query' => 'mutation { signIn(input: {}) { id } }', 'variables' => 'not-an-array'],
                JSON_THROW_ON_ERROR
            )
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame(['signin_ip'], array_keys($byName));
    }

    public function testEmailFromVariablesIsLowercasedAndTrimmed(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($input: signInInput!) { signIn(input: $input) { user { id } } }',
            ['input' => ['email' => "  USER@Example.COM\t"]],
            '198.51.100.6'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:user@example.com', $byName['signin_email']);
    }

    public function testTopLevelVariablesAreUsedWhenInputKeyMissing(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($email: String!) { signIn(email: $email) { user { id } } }',
            ['email' => 'top.level@example.com'],
            '198.51.100.7'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:top.level@example.com', $byName['signin_email']);
    }

    public function testDuplicateMutationOccurrencesProduceSingleTargetSet(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { refreshToken(input: {a: 1}) { id } refreshToken(input: {b: 2}) { id } }',
            [],
            '198.51.100.8'
        );

        $targets = $this->createGraphQlResolver()->resolve($request);

        self::assertSame(
            [['name' => 'refresh_token', 'key' => 'ip:198.51.100.8']],
            $targets
        );
    }

    public function testCompleteTwoFactorWithoutRepositoryOnlyThrottlesByIp(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { completeTwoFactor(input: {pendingSessionId: "s-1"}) { id } }',
            [],
            '198.51.100.41'
        );

        $byName = array_column(
            $this->createGraphQlResolver(null, null)->resolve($request),
            'key',
            'name'
        );

        self::assertArrayHasKey('twofa_verification_ip', $byName);
        self::assertArrayNotHasKey('twofa_verification_user', $byName);
    }

    public function testCompleteTwoFactorWithUnknownPendingSessionOnlyThrottlesByIp(): void
    {
        $sessionId = $this->faker->uuid();
        $repository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $repository->method('findById')->with($sessionId)->willReturn(null);

        $request = $this->createGraphQlRequest(
            'mutation { completeTwoFactor(input: $input) { id } }',
            ['input' => ['pendingSessionId' => $sessionId]],
            '198.51.100.42'
        );

        $resolver = new ApiRateLimitGraphQlResolver(
            $this->createJsonSerializer(),
            $this->createClientIdentityResolver(),
            $repository
        );
        $byName = array_column($resolver->resolve($request), 'key', 'name');

        self::assertArrayHasKey('twofa_verification_ip', $byName);
        self::assertArrayNotHasKey('twofa_verification_user', $byName);
    }

    public function testCompleteTwoFactorWithEmptyUserIdOnlyThrottlesByIp(): void
    {
        $sessionId = $this->faker->uuid();
        $repository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $repository->method('findById')->with($sessionId)->willReturn(
            new PendingTwoFactor($sessionId, '', new DateTimeImmutable())
        );

        $request = $this->createGraphQlRequest(
            'mutation { completeTwoFactor(input: $input) { id } }',
            ['input' => ['pendingSessionId' => $sessionId]],
            '198.51.100.43'
        );

        $resolver = new ApiRateLimitGraphQlResolver(
            $this->createJsonSerializer(),
            $this->createClientIdentityResolver(),
            $repository
        );
        $byName = array_column($resolver->resolve($request), 'key', 'name');

        self::assertArrayHasKey('twofa_verification_ip', $byName);
        self::assertArrayNotHasKey('twofa_verification_user', $byName);
    }

    public function testEmptyEmailStringIsTreatedAsAbsent(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($input: signInInput!) { signIn(input: $input) { user { id } } }',
            ['input' => ['email' => '']],
            '198.51.100.44'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertArrayHasKey('signin_ip', $byName);
        self::assertArrayNotHasKey('signin_email', $byName);
    }

    public function testCompleteTwoFactorWithoutSessionDoesNotQueryRepository(): void
    {
        $repository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $repository->expects(self::never())->method('findById');

        $request = $this->createGraphQlRequest(
            'mutation { completeTwoFactor(input: {twoFactorCode: "123456"}) { user { id } } }',
            [],
            '198.51.100.45'
        );

        $resolver = new ApiRateLimitGraphQlResolver(
            $this->createJsonSerializer(),
            $this->createClientIdentityResolver(),
            $repository
        );
        $byName = array_column($resolver->resolve($request), 'key', 'name');

        self::assertArrayHasKey('twofa_verification_ip', $byName);
        self::assertArrayNotHasKey('twofa_verification_user', $byName);
    }

    public function testEmailIsResolvedRegardlessOfItsPositionInInput(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($input: signInInput!) { signIn(input: $input) { user { id } } }',
            ['input' => ['password' => 'secret', 'email' => 'positioned@example.com']],
            '198.51.100.47'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:positioned@example.com', $byName['signin_email']);
    }

    public function testNonArrayInputKeyIsIgnored(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '198.51.100.46', 'CONTENT_TYPE' => 'application/json'],
            json_encode(
                [
                    'query' => 'mutation { signIn(input: {}) { id } }',
                    'variables' => ['input' => 'not-an-array'],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame(['signin_ip'], array_keys($byName));
    }

    public function testNonStringQueryFieldReturnsNoTargets(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['query' => 12345], JSON_THROW_ON_ERROR)
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNestedInputObjectIsDecodedAssociatively(): void
    {
        $request = Request::create(
            self::ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                [
                    'query' => 'mutation { signIn(input: {}) { id } }',
                    'variables' => ['input' => ['email' => 'nested@example.com']],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:nested@example.com', $byName['signin_email']);
    }

    public function testSignOutAllMutationProducesUserLimiterForAuthenticatedRequest(): void
    {
        $userId = $this->faker->uuid();
        $jwtConverter = $this->createMock(
            \App\Shared\Application\Converter\JwtTokenConverterInterface::class
        );
        $jwtConverter->method('decode')->willReturn($this->buildValidPayload(['sub' => $userId]));

        $request = $this->createGraphQlRequest('mutation { signOutAll(input: {}) { id } }');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        $resolver = $this->createGraphQlResolver(
            $this->createClientIdentityResolver($jwtConverter)
        );
        $byName = array_column($resolver->resolve($request), 'key', 'name');

        self::assertSame('user:' . $userId, $byName['signout_all']);
        self::assertArrayNotHasKey('signout', $byName);
    }

    /**
     * @param array<string, array<string, scalar|null>|scalar|null> $variables
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
}
