<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Shared\Application\EventListener\ApiRateLimitListener;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitAuthTargetResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ApiRateLimitListenerTest extends UnitTestCase
{
    public function testIgnoresSubRequest(): void
    {
        $listener = new ApiRateLimitListener([
            'registration' => $this->createNeverCalledFactory(),
            'global_api_anonymous' => $this->createNeverCalledFactory(),
        ]);
        $event = $this->createRequestEvent(
            '/api/users',
            'POST',
            HttpKernelInterface::SUB_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresUnsupportedPath(): void
    {
        $listener = new ApiRateLimitListener([
            'global_api_anonymous' => $this->createNeverCalledFactory(),
        ]);
        $event = $this->createRequestEvent('/health', 'GET');

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testThrowsLogicExceptionWhenLimiterNotConfigured(): void
    {
        $listener = new ApiRateLimitListener([]);
        $event = $this->createRequestEvent('/api/users', 'POST');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Rate limiter "registration" is not configured.');

        $listener($event);
    }

    public function testGlobalLimiterRejectsAfterEndpointLimiterPasses(): void
    {
        $listener = new ApiRateLimitListener([
            'registration' => $this->createLimiterFactoryMock('ip:127.0.0.1', true),
            'global_api_anonymous' => $this->createLimiterFactoryMock(
                'ip:127.0.0.1',
                false,
                new DateTimeImmutable('+30 seconds')
            ),
        ]);
        $event = $this->createRequestEvent('/api/users', 'POST');
        $listener($event);
        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());
    }

    public function testAppliesEndpointLimiterThenGlobalAnonymousLimiter(): void
    {
        $registrationLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: true
        );
        $globalLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: true
        );

        $listener = new ApiRateLimitListener([
            'registration' => $registrationLimiter,
            'global_api_anonymous' => $globalLimiter,
        ]);
        $event = $this->createRequestEvent('/api/users', 'POST');

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testReturnsProblemResponseWhenEndpointLimiterRejects(): void
    {
        $listener = new ApiRateLimitListener([
            'registration' => $this->createLimiterFactoryMock(
                'ip:127.0.0.1',
                false,
                new DateTimeImmutable('+60 seconds')
            ),
            'global_api_anonymous' => $this->createNeverCalledFactory(),
        ]);
        $event = $this->createRequestEvent('/api/users', 'POST');
        $listener($event);
        $this->assertRateLimitedResponse($event);
    }

    public function testUsesAuthenticatedGlobalLimiterForBearerRequests(): void
    {
        $token = 'valid-auth-jwt';
        $globalLimiter = $this->createLimiterFactoryMock('ip:127.0.0.1', true);
        $listener = $this->createListenerWithValidatedToken(
            ['global_api_authenticated' => $globalLimiter],
            $token,
            $this->createValidJwtPayload($this->faker->uuid())
        );
        $event = $this->createBearerRequestEvent('/api/health', 'GET', $token);
        $listener($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testUsesUserSpecificKeyForUserUpdate(): void
    {
        $token = 'valid-update-jwt';
        $userId = '8be90127-9840-4235-a6da-39b8debfb260';
        $userLimiter = $this->createLimiterFactoryMock(sprintf('user:%s', $userId), true);
        $globalLimiter = $this->createLimiterFactoryMock('ip:127.0.0.1', true);
        $listener = $this->createListenerWithValidatedToken(
            ['user_update' => $userLimiter, 'global_api_authenticated' => $globalLimiter],
            $token,
            $this->createValidJwtPayload($userId)
        );
        $event = $this->createBearerRequestEvent(
            sprintf('/api/users/%s', $userId),
            'PATCH',
            $token
        );
        $listener($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testUsesSignInIpAndEmailLimiters(): void
    {
        $email = 'rate-email@test.com';
        $signinIpLimiter = $this->createLimiterFactoryMock('ip:127.0.0.1', true);
        $signinEmailLimiter = $this->createLimiterFactoryMock(sprintf('email:%s', $email), true);
        $globalLimiter = $this->createLimiterFactoryMock('ip:127.0.0.1', true);
        $listener = new ApiRateLimitListener([
            'signin_ip' => $signinIpLimiter,
            'signin_email' => $signinEmailLimiter,
            'global_api_anonymous' => $globalLimiter,
        ]);
        $event = $this->createRequestEvent(
            '/api/signin',
            'POST',
            HttpKernelInterface::MAIN_REQUEST,
            [],
            json_encode(['email' => $email, 'password' => 'passWORD1'], JSON_THROW_ON_ERROR)
        );
        $listener($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testUsesTokenSubjectForTwoFactorSetupLimiter(): void
    {
        $token = 'valid-twofa-jwt';
        $userId = '8be90127-9840-4235-a6da-39b8debfb260';
        $twoFactorSetupLimiter = $this->createLimiterFactoryMock(sprintf('user:%s', $userId), true);
        $globalLimiter = $this->createLimiterFactoryMock('ip:127.0.0.1', true);
        $listener = $this->createListenerWithValidatedToken(
            ['twofa_setup' => $twoFactorSetupLimiter, 'global_api_authenticated' => $globalLimiter],
            $token,
            $this->createValidJwtPayload($userId)
        );
        $event = $this->createBearerRequestEvent('/api/2fa/setup', 'POST', $token);
        $listener($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testUsesAnonymousGlobalLimiterForInvalidBearerRequests(): void
    {
        $globalLimiter = $this->createLimiterFactoryMock('ip:127.0.0.1', true);
        $listener = new ApiRateLimitListener(['global_api_anonymous' => $globalLimiter]);
        $event = $this->createBearerRequestEvent('/api/health', 'GET', 'invalid-token');
        $listener($event);
        $this->assertFalse($event->hasResponse());
    }

    private function assertRateLimitedResponse(RequestEvent $event): void
    {
        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertIsNumeric($response->headers->get('Retry-After'));
        $this->assertGreaterThan(0, (int) $response->headers->get('Retry-After'));
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('/errors/429', $payload['type']);
        $this->assertSame('Too Many Requests', $payload['title']);
        $this->assertSame(429, $payload['status']);
    }

    private function createBearerRequestEvent(
        string $path,
        string $method,
        string $token
    ): RequestEvent {
        return $this->createRequestEvent(
            $path,
            $method,
            HttpKernelInterface::MAIN_REQUEST,
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
    }

    /**
     * @param array<string, string> $server
     */
    private function createRequestEvent(
        string $path,
        string $method,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        array $server = [],
        ?string $content = null
    ): RequestEvent {
        $request = Request::create(
            $path,
            $method,
            [],
            [],
            [],
            array_merge(['REMOTE_ADDR' => '127.0.0.1'], $server),
            $content
        );

        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType
        );
    }

    private function createNeverCalledFactory(): RateLimiterFactory&MockObject
    {
        $factory = $this->createMock(RateLimiterFactory::class);
        $factory
            ->expects($this->never())
            ->method('create');

        return $factory;
    }

    private function createLimiterFactoryMock(
        string $expectedKey,
        bool $accepted,
        ?DateTimeImmutable $retryAfter = null
    ): RateLimiterFactory&MockObject {
        $factory = $this->createMock(RateLimiterFactory::class);
        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);
        $factory->expects($this->once())->method('create')
            ->with($expectedKey)->willReturn($limiter);
        $limiter->expects($this->once())->method('consume')
            ->with(1)->willReturn($rateLimit);
        $rateLimit->expects($this->once())->method('isAccepted')->willReturn($accepted);
        $rateLimit->method('getRetryAfter')
            ->willReturn($retryAfter ?? new DateTimeImmutable('+1 second'));
        return $factory;
    }

    /**
     * @param array<string, RateLimiterFactory> $limiterFactories
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     */
    private function createListenerWithValidatedToken(
        array $limiterFactories,
        string $token,
        array $payload
    ): ApiRateLimitListener {
        $jwtConverter = $this->createMock(JwtTokenConverterInterface::class);
        $jwtConverter->method('decode')->willReturnCallback(
            static function (string $candidateToken) use ($token, $payload): ?array {
                return $candidateToken === $token ? $payload : null;
            }
        );
        $clientIdentityResolver =
            new ApiRateLimitClientIdentityResolver($jwtConverter);
        $authTargetResolver =
            new ApiRateLimitAuthTargetResolver(null, $clientIdentityResolver);
        $requestMatcher = new ApiRateLimitRequestResolver(
            $clientIdentityResolver,
            $authTargetResolver
        );
        return new ApiRateLimitListener(
            $limiterFactories,
            $requestMatcher
        );
    }

    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    private function createValidJwtPayload(string $subject): array
    {
        $now = time();

        return [
            'sub' => $subject,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => $now - 1,
            'exp' => $now + 60,
            'roles' => ['ROLE_USER'],
        ];
    }
}
