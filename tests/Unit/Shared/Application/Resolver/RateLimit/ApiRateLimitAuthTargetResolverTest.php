<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Decoder\JwtTokenDecoderInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitAuthTargetResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitAuthTargetResolverTest extends UnitTestCase
{
    private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository;
    private JwtTokenDecoderInterface $jwtDecoder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->pendingTwoFactorRepository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->jwtDecoder = $this->createMock(JwtTokenDecoderInterface::class);
    }

    public function testResolveReturnsEmptyArrayForUnrelatedPath(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/users', 'POST');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyArrayForGetRequest(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/signin', 'GET');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveSignInLimitersReturnsIpLimiterWhenNoEmailInBody(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/signin', 'POST', [], [], [], ['REMOTE_ADDR' => $clientIp]);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('signin_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
    }

    public function testResolveSignInLimitersReturnsBothLimitersWhenEmailPresent(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email], JSON_THROW_ON_ERROR)
        );

        $result = $resolver->resolve($request);

        self::assertCount(2, $result);
        self::assertSame('signin_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
        self::assertSame('signin_email', $result[1]['name']);
        self::assertSame('email:' . strtolower(trim($email)), $result[1]['key']);
    }

    public function testResolveSignInLimitersUsesDefaultIpWhenClientIpIsNull(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/signin', 'POST', [], [], [], ['REMOTE_ADDR' => '']);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertStringStartsWith('ip:', $result[0]['key']);
    }

    public function testResolveReturnsEmptyArrayForPutSignIn(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/signin', 'PUT');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyArrayForDeleteSignIn(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/signin', 'DELETE');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveSignInTwoFactorReturnsIpLimiterWithNoPendingSessionId(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = new ApiRateLimitAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = Request::create('/api/signin/2fa', 'POST', [], [], [], ['REMOTE_ADDR' => $clientIp]);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_verification_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
    }

    public function testResolveSignInTwoFactorReturnsIpLimiterWhenRepositoryIsNull(): void
    {
        $clientIp = $this->faker->ipv4();
        $sessionId = $this->faker->uuid();
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $sessionId], JSON_THROW_ON_ERROR)
        );

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_verification_ip', $result[0]['name']);
    }

    public function testResolveSignInTwoFactorReturnsIpLimiterWhenSessionNotFound(): void
    {
        $clientIp = $this->faker->ipv4();
        $sessionId = $this->faker->uuid();
        $this->pendingTwoFactorRepository->method('findById')->with($sessionId)->willReturn(null);

        $resolver = new ApiRateLimitAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $sessionId], JSON_THROW_ON_ERROR)
        );

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_verification_ip', $result[0]['name']);
    }

    public function testResolveSignInTwoFactorReturnsBothLimitersWhenSessionFound(): void
    {
        $clientIp = $this->faker->ipv4();
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $pendingSession = new PendingTwoFactor($sessionId, $userId, new DateTimeImmutable());
        $this->pendingTwoFactorRepository->method('findById')->with($sessionId)->willReturn($pendingSession);

        $resolver = new ApiRateLimitAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $sessionId], JSON_THROW_ON_ERROR)
        );

        $result = $resolver->resolve($request);

        self::assertCount(2, $result);
        self::assertSame('twofa_verification_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
        self::assertSame('twofa_verification_user', $result[1]['name']);
        self::assertSame('user:' . $userId, $result[1]['key']);
    }

    public function testResolveSignInTwoFactorUsesSnakeCasePendingSessionIdKey(): void
    {
        $clientIp = $this->faker->ipv4();
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $pendingSession = new PendingTwoFactor($sessionId, $userId, new DateTimeImmutable());
        $this->pendingTwoFactorRepository->method('findById')->with($sessionId)->willReturn($pendingSession);

        $resolver = new ApiRateLimitAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['pending_session_id' => $sessionId], JSON_THROW_ON_ERROR)
        );

        $result = $resolver->resolve($request);

        self::assertCount(2, $result);
        self::assertSame('twofa_verification_user', $result[1]['name']);
        self::assertSame('user:' . $userId, $result[1]['key']);
    }

    public function testResolveReturnsTwoFaSetupLimiterWhenUserAuthenticated(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $resolver = new ApiRateLimitAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/users/2fa/setup', 'POST');
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
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $resolver = new ApiRateLimitAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/users/2fa/confirm', 'POST');
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
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $resolver = new ApiRateLimitAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/users/2fa/disable', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_disable', $result[0]['name']);
        self::assertSame('user:' . $subject, $result[0]['key']);
    }

    public function testResolveReturnsEmptyForTwoFaSetupWhenNotAuthenticated(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/users/2fa/setup', 'POST');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForTwoFaConfirmWhenNotAuthenticated(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/users/2fa/confirm', 'POST');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForTwoFaDisableWhenNotAuthenticated(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver();
        $request = Request::create('/api/users/2fa/disable', 'POST');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForTwoFaSetupWithGetMethod(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $resolver = new ApiRateLimitAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/users/2fa/setup', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveReturnsEmptyForUnknownTwoFaPath(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $clientIdentityResolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $resolver = new ApiRateLimitAuthTargetResolver(null, $clientIdentityResolver);

        $request = Request::create('/api/users/2fa/unknown', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveSignInTwoFactorReturnsEmptyForGetMethod(): void
    {
        $resolver = new ApiRateLimitAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = Request::create('/api/signin/2fa', 'GET');

        self::assertSame([], $resolver->resolve($request));
    }

    public function testResolveMergesAllApplicableLimiters(): void
    {
        $subject = $this->faker->uuid();
        $token = $this->faker->sha256();
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $clientIp = $this->faker->ipv4();

        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $pendingSession = new PendingTwoFactor($sessionId, $userId, new DateTimeImmutable());
        $this->pendingTwoFactorRepository->method('findById')->willReturn($pendingSession);

        $clientIdentityResolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $resolver = new ApiRateLimitAuthTargetResolver(
            $this->pendingTwoFactorRepository,
            $clientIdentityResolver
        );

        $signInRequest = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->faker->email()], JSON_THROW_ON_ERROR)
        );
        $signInRequest->headers->set('Authorization', 'Bearer ' . $token);

        $signInResult = $resolver->resolve($signInRequest);
        self::assertCount(2, $signInResult);
    }

    public function testResolveSignInTwoFactorDoesNotAddUserLimiterWhenUserIdIsEmpty(): void
    {
        $clientIp = $this->faker->ipv4();
        $sessionId = $this->faker->uuid();

        $pendingSession = new PendingTwoFactor($sessionId, '', new DateTimeImmutable());
        $this->pendingTwoFactorRepository->method('findById')->with($sessionId)->willReturn($pendingSession);

        $resolver = new ApiRateLimitAuthTargetResolver($this->pendingTwoFactorRepository);
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $sessionId], JSON_THROW_ON_ERROR)
        );

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('twofa_verification_ip', $result[0]['name']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    private function buildValidPayload(array $overrides = []): array
    {
        $now = time();

        /** @var array<string, array<int, string>|bool|float|int|string|null> $base */
        $base = [
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $this->faker->uuid(),
            'nbf' => $now - 60,
            'exp' => $now + 3600,
        ];

        return array_merge($base, $overrides);
    }
}
