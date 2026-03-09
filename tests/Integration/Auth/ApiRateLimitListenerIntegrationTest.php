<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Uid\Factory\UlidFactory;

final class ApiRateLimitListenerIntegrationTest extends AuthIntegrationTestCase
{
    private HttpKernelInterface $httpKernel;
    private CacheItemPoolInterface $cachePool;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;

        $cachePool = $this->container->get('cache.app');
        $this->assertInstanceOf(CacheItemPoolInterface::class, $cachePool);
        $this->cachePool = $cachePool;

        $this->cachePool->clear();
    }

    public function testGlobalAnonymousLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter(
            'global_api_anonymous',
            'ip:127.0.0.1',
            $this->resolveLimit('GLOBAL_API_ANONYMOUS_RATE_LIMIT_MAX_REQUESTS', 100)
        );
        $response = $this->handleJsonRequest('/api/health', Request::METHOD_GET);
        $this->assertRateLimitResponse($response);
    }

    public function testRegistrationLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter(
            'registration',
            'ip:127.0.0.1',
            $this->resolveLimit('REGISTRATION_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'email' => $this->faker->safeEmail(),
            'initials' => $this->faker->name(),
            'password' => 'passWORD1',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/users', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    public function testSignInIpLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter(
            'signin_ip',
            'ip:127.0.0.1',
            $this->resolveLimit('SIGNIN_IP_RATE_LIMIT_MAX_REQUESTS', 10)
        );
        $content = json_encode([
            'email' => $this->faker->safeEmail(),
            'password' => 'passWORD1',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/signin', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    public function testSignInEmailLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $email = 'signin-email-limit@test.com';
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', $email),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'email' => $email,
            'password' => 'passWORD1',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/signin', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    public function testTwoFactorSetupLimiterUsesJwtSubjectAndReturns429(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb260';
        $this->exhaustLimiter(
            'twofa_setup',
            sprintf('user:%s', $userId),
            $this->resolveLimit('TWOFA_SETUP_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $response = $this->handleJsonRequest(
            '/api/2fa/setup',
            Request::METHOD_POST,
            json_encode([], JSON_THROW_ON_ERROR),
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->createBearerTokenForUser($userId))]
        );
        $this->assertRateLimitResponse($response);
    }

    public function testTwoFactorVerificationLimitersReturn429(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb261';
        $pendingSessionId = (string) $this->container->get(UlidFactory::class)->create();
        $this->savePendingTwoFactor($pendingSessionId, $userId);
        $this->exhaustLimiter(
            'twofa_verification_user',
            sprintf('user:%s', $userId),
            $this->resolveLimit('TWOFA_VERIFICATION_USER_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $this->exhaustLimiter(
            'twofa_verification_ip',
            'ip:127.0.0.1',
            $this->resolveLimit('TWOFA_VERIFICATION_IP_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'pendingSessionId' => $pendingSessionId,
            'twoFactorCode' => '123456',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/signin/2fa', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    private function savePendingTwoFactor(string $pendingSessionId, string $userId): void
    {
        $this->container->get(PendingTwoFactorRepositoryInterface::class)->save(
            new PendingTwoFactor(
                $pendingSessionId,
                $userId,
                new \DateTimeImmutable('-10 seconds'),
                new \DateTimeImmutable('+5 minutes')
            )
        );
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    private function handleJsonRequest(
        string $uri,
        string $method,
        ?string $content = null,
        array $extraHeaders = []
    ): Response {
        $serverParams = array_merge([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $extraHeaders);

        return $this->httpKernel->handle(
            Request::create($uri, $method, [], [], [], $serverParams, $content)
        );
    }

    private function exhaustLimiter(string $limiterName, string $key, int $tokens): void
    {
        $factory = $this->container->get(sprintf('limiter.%s', $limiterName));
        $this->assertInstanceOf(RateLimiterFactory::class, $factory);

        $factory->create($key)->consume($tokens);
    }

    private function resolveLimit(string $envKey, int $fallback): int
    {
        $rawValue = getenv($envKey);
        if (!is_string($rawValue)) {
            return $fallback;
        }

        $resolved = (int) trim($rawValue);

        return $resolved > 0 ? $resolved : $fallback;
    }

    private function assertRateLimitResponse(Response $response): void
    {
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );

        $retryAfter = $response->headers->get('Retry-After');
        $this->assertIsString($retryAfter);
        $this->assertMatchesRegularExpression('/^[1-9][0-9]*$/', $retryAfter);

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('/errors/429', $payload['type']);
        $this->assertSame(429, $payload['status']);
        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('detail', $payload);
    }
}
