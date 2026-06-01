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

abstract class ApiRateLimitListenerTestCase extends AuthIntegrationTestCase
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

    protected function savePendingTwoFactor(string $pendingSessionId, string $userId): void
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

    protected function createSelectedPasskeySigninPayload(string $email): string
    {
        $decoyEmail = $this->faker->safeEmail();

        return json_encode([
            'query' => sprintf(<<<'GRAPHQL'
mutation Decoy {
  passkeySignInOptionsUser(input: { email: "%s" }) { user { challengeId } }
}
mutation Real($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) { user { challengeId } }
}
GRAPHQL, $decoyEmail),
            'operationName' => 'Real',
            'variables' => ['e' => $email],
            'email' => $this->faker->safeEmail(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{operations: string}
     */
    protected function createMultipartPasskeySigninPayload(string $email): array
    {
        return [
            'operations' => json_encode([
                'query' => <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) { user { challengeId } }
}
GRAPHQL,
                'variables' => ['input' => ['email' => $email]],
            ], JSON_THROW_ON_ERROR),
        ];
    }

    protected function createPasskeySigninCompleteGraphQlPayload(string $email): string
    {
        return json_encode([
            'query' => sprintf(<<<'GRAPHQL'
mutation {
  passkeySignInCompleteUser(input: {
    challengeId: "%s"
    credential: { email: "%s" }
  }) {
    user { accessToken }
  }
}
GRAPHQL, $this->faker->uuid(), $email),
        ], JSON_THROW_ON_ERROR);
    }

    protected function createPasskeyRegistrationOptionsGraphQlPayload(): string
    {
        return json_encode([
            'query' => <<<'GRAPHQL'
mutation {
  passkeyRegistrationOptionsUser(input: {}) {
    user { challengeId }
  }
}
GRAPHQL,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{HTTP_AUTHORIZATION: string}
     */
    protected function createAuthenticatedHeader(): array
    {
        return [
            'HTTP_AUTHORIZATION' => sprintf(
                'Bearer %s',
                $this->createBearerTokenForUser($this->faker->uuid())
            ),
        ];
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    protected function handleJsonRequest(
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

    /**
     * @param array<string, string> $parameters
     */
    protected function handleFormRequest(string $uri, string $method, array $parameters): Response
    {
        return $this->httpKernel->handle(
            Request::create(
                $uri,
                $method,
                $parameters,
                [],
                [],
                [
                    'REMOTE_ADDR' => '127.0.0.1',
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'multipart/form-data; boundary=----rate-limit',
                ]
            )
        );
    }

    protected function exhaustLimiter(string $limiterName, string $key, int $tokens): void
    {
        $factory = $this->container->get(sprintf('limiter.%s', $limiterName));
        $this->assertInstanceOf(RateLimiterFactory::class, $factory);

        $factory->create($key)->consume($tokens);
    }

    protected function resolveLimit(string $envKey, int $fallback): int
    {
        $rawValue = getenv($envKey);
        if (!is_string($rawValue)) {
            return $fallback;
        }

        $resolved = (int) trim($rawValue);

        return $resolved > 0 ? $resolved : $fallback;
    }

    protected function assertRateLimitResponse(Response $response): void
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
