<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\ApiRateLimitListener;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
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
        $listener = new ApiRateLimitListener([]);
        $event = $this->createRequestEvent(
            '/api/health',
            'GET',
            HttpKernelInterface::SUB_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
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
        $registrationLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: false,
            retryAfter: new DateTimeImmutable('+60 seconds')
        );

        $listener = new ApiRateLimitListener([
            'registration' => $registrationLimiter,
            'global_api_anonymous' => $this->createNeverCalledFactory(),
        ]);
        $event = $this->createRequestEvent('/api/users', 'POST');

        $listener($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );
        $this->assertIsNumeric($response->headers->get('Retry-After'));
        $this->assertGreaterThan(
            0,
            (int) $response->headers->get('Retry-After')
        );

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('/errors/429', $payload['type']);
        $this->assertSame('Too Many Requests', $payload['title']);
        $this->assertSame(429, $payload['status']);
    }

    public function testUsesAuthenticatedGlobalLimiterForBearerRequests(): void
    {
        $globalLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: true
        );

        $listener = new ApiRateLimitListener([
            'global_api_authenticated' => $globalLimiter,
        ]);
        $event = $this->createRequestEvent(
            '/api/health',
            'GET',
            HttpKernelInterface::MAIN_REQUEST,
            [
                'HTTP_AUTHORIZATION' => 'Bearer some-jwt',
            ]
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testUsesUserSpecificKeyForUserUpdate(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb260';
        $userLimiter = $this->createLimiterFactoryMock(
            expectedKey: sprintf('user:%s', $userId),
            accepted: true
        );
        $globalLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: true
        );

        $listener = new ApiRateLimitListener([
            'user_update' => $userLimiter,
            'global_api_authenticated' => $globalLimiter,
        ]);
        $event = $this->createRequestEvent(
            sprintf('/api/users/%s', $userId),
            'PATCH',
            HttpKernelInterface::MAIN_REQUEST,
            [
                'HTTP_AUTHORIZATION' => 'Bearer some-jwt',
            ]
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testUsesSignInIpAndEmailLimiters(): void
    {
        $email = 'rate-email@test.com';
        $signinIpLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: true
        );
        $signinEmailLimiter = $this->createLimiterFactoryMock(
            expectedKey: sprintf('email:%s', $email),
            accepted: true
        );
        $globalLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: true
        );

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
            json_encode(
                ['email' => $email, 'password' => 'passWORD1'],
                JSON_THROW_ON_ERROR
            )
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testUsesTokenSubjectForTwoFactorSetupLimiter(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb260';
        $twoFactorSetupLimiter = $this->createLimiterFactoryMock(
            expectedKey: sprintf('user:%s', $userId),
            accepted: true
        );
        $globalLimiter = $this->createLimiterFactoryMock(
            expectedKey: 'ip:127.0.0.1',
            accepted: true
        );

        $listener = new ApiRateLimitListener([
            'twofa_setup' => $twoFactorSetupLimiter,
            'global_api_authenticated' => $globalLimiter,
        ]);
        $event = $this->createRequestEvent(
            '/api/users/2fa/setup',
            'POST',
            HttpKernelInterface::MAIN_REQUEST,
            [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->createJwt($userId)),
            ]
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
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

    private function createJwt(string $sub): string
    {
        $header = $this->encodeBase64Url(
            json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)
        );
        $payload = $this->encodeBase64Url(
            json_encode(['sub' => $sub], JSON_THROW_ON_ERROR)
        );

        return sprintf('%s.%s.signature', $header, $payload);
    }

    private function encodeBase64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
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

        $factory
            ->expects($this->once())
            ->method('create')
            ->with($expectedKey)
            ->willReturn($limiter);

        $limiter
            ->expects($this->once())
            ->method('consume')
            ->with(1)
            ->willReturn($rateLimit);

        $rateLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn($accepted);

        $rateLimit
            ->method('getRetryAfter')
            ->willReturn($retryAfter ?? new DateTimeImmutable('+1 second'));

        return $factory;
    }
}
