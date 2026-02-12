<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Uid\Factory\UlidFactory;

final class ApiRateLimitListenerIntegrationTest extends IntegrationTestCase
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
        $this->exhaustLimiter('global_api_anonymous', 'ip:127.0.0.1', 100);

        $response = $this->httpKernel->handle(Request::create(
            '/api/health',
            Request::METHOD_GET,
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_ACCEPT' => 'application/json',
            ]
        ));

        $this->assertRateLimitResponse($response);
    }

    public function testRegistrationLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter('registration', 'ip:127.0.0.1', 5);

        $response = $this->httpKernel->handle(Request::create(
            '/api/users',
            Request::METHOD_POST,
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'email' => $this->faker->safeEmail(),
                'initials' => $this->faker->name(),
                'password' => 'passWORD1',
            ], JSON_THROW_ON_ERROR)
        ));

        $this->assertRateLimitResponse($response);
    }

    public function testSignInIpLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter('signin_ip', 'ip:127.0.0.1', 10);

        $response = $this->httpKernel->handle(Request::create(
            '/api/signin',
            Request::METHOD_POST,
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'email' => $this->faker->safeEmail(),
                'password' => 'passWORD1',
            ], JSON_THROW_ON_ERROR)
        ));

        $this->assertRateLimitResponse($response);
    }

    public function testSignInEmailLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $email = 'signin-email-limit@test.com';
        $this->exhaustLimiter('signin_email', sprintf('email:%s', $email), 5);

        $response = $this->httpKernel->handle(Request::create(
            '/api/signin',
            Request::METHOD_POST,
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'email' => $email,
                'password' => 'passWORD1',
            ], JSON_THROW_ON_ERROR)
        ));

        $this->assertRateLimitResponse($response);
    }

    public function testTwoFactorSetupLimiterUsesJwtSubjectAndReturns429(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb260';
        $this->exhaustLimiter('twofa_setup', sprintf('user:%s', $userId), 5);

        $response = $this->httpKernel->handle(Request::create(
            '/api/users/2fa/setup',
            Request::METHOD_POST,
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf(
                    'Bearer %s',
                    $this->createBearerTokenForUser($userId)
                ),
            ],
            json_encode([], JSON_THROW_ON_ERROR)
        ));

        $this->assertRateLimitResponse($response);
    }

    public function testTwoFactorVerificationLimitersReturn429(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb261';
        $pendingSessionId = (string) $this->container
            ->get(UlidFactory::class)
            ->create();

        $this->container->get(PendingTwoFactorRepositoryInterface::class)->save(
            new PendingTwoFactor(
                $pendingSessionId,
                $userId,
                new \DateTimeImmutable('-10 seconds'),
                new \DateTimeImmutable('+5 minutes')
            )
        );

        $this->exhaustLimiter(
            'twofa_verification_user',
            sprintf('user:%s', $userId),
            5
        );
        $this->exhaustLimiter('twofa_verification_ip', 'ip:127.0.0.1', 5);

        $response = $this->httpKernel->handle(Request::create(
            '/api/signin/2fa',
            Request::METHOD_POST,
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'pendingSessionId' => $pendingSessionId,
                'twoFactorCode' => '123456',
            ], JSON_THROW_ON_ERROR)
        ));

        $this->assertRateLimitResponse($response);
    }

    private function exhaustLimiter(string $limiterName, string $key, int $tokens): void
    {
        $factory = $this->container->get(sprintf('limiter.%s', $limiterName));
        $this->assertInstanceOf(RateLimiterFactory::class, $factory);

        $factory->create($key)->consume($tokens);
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
