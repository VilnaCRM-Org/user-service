<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitClientIdentityResolverEmailSessionTest extends RateLimitClientTestCase
{
    public function testResolveSignInEmailReturnsNormalizedEmailFromJsonPayload(): void
    {
        $email = '  ' . strtoupper($this->faker->email()) . '  ';
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email], JSON_THROW_ON_ERROR)
        );

        self::assertSame(strtolower(trim($email)), $resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailReturnsNormalizedEmailFromFormPayload(): void
    {
        $rawEmail = 'USER@Example.COM';
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['email' => $rawEmail])
        );

        self::assertSame('user@example.com', $resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailTrimsWhitespace(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => '  test@example.com  '], JSON_THROW_ON_ERROR)
        );

        self::assertSame('test@example.com', $resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailReturnsNullWhenEmailMissing(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/signin', 'POST');

        self::assertNull($resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailReturnsNullWhenBodyIsEmpty(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/signin', 'POST', [], [], [], [], '');

        self::assertNull($resolver->resolveSignInEmail($request));
    }

    public function testResolvePendingSessionIdReturnsCamelCaseKey(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $sessionId], JSON_THROW_ON_ERROR)
        );

        self::assertSame($sessionId, $resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdReturnsSnakeCaseKey(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['pending_session_id' => $sessionId], JSON_THROW_ON_ERROR)
        );

        self::assertSame($sessionId, $resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdPrefersCamelCaseOverSnakeCase(): void
    {
        $camelId = $this->faker->uuid();
        $snakeId = $this->faker->uuid();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['pendingSessionId' => $camelId, 'pending_session_id' => $snakeId],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($camelId, $resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdReturnsNullWhenMissing(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/signin/2fa', 'POST');

        self::assertNull($resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdFromFormPayload(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['pending_session_id' => $sessionId])
        );

        self::assertSame($sessionId, $resolver->resolvePendingSessionId($request));
    }
}
