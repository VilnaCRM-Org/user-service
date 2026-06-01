<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventListener;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlDocumentResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspector;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventListener\PasskeyProductionReadinessListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class PasskeyProductionReadinessListenerTest extends UnitTestCase
{
    private const TRAFFIC_DISABLED_DETAIL =
        'Passkey production traffic is disabled until production monitoring and alerts are ready.';
    private const MONITORING_NOT_READY_DETAIL_PREFIX =
        'Passkey production traffic requires latency, traffic, error-rate, ';
    private const MONITORING_NOT_READY_DETAIL_SUFFIX =
        'active-challenge, expired-challenge, and TTL-index monitoring before enablement.';
    private const MONITORING_NOT_READY_DETAIL =
        self::MONITORING_NOT_READY_DETAIL_PREFIX . self::MONITORING_NOT_READY_DETAIL_SUFFIX;

    public function testNonProductionPasskeyTrafficIsAllowed(): void
    {
        $listener = $this->createListener('test', false, false);
        $event = $this->createRequestEvent('/api/passkeys/signin/options', Request::METHOD_POST);

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testSubRequestsAreIgnored(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/passkeys/signin/options',
            Request::METHOD_POST,
            requestType: HttpKernelInterface::SUB_REQUEST
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testProductionPasskeyRestTrafficIsBlockedWhenTrafficFlagDisabled(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent('/api/passkeys/signin/options', Request::METHOD_POST);

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionPasskeyRestTrafficRequiresMonitoringReadiness(): void
    {
        $listener = $this->createListener('prod', true, false);
        $event = $this->createRequestEvent('/api/passkeys/signup/options', Request::METHOD_POST);

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::MONITORING_NOT_READY_DETAIL
        );
    }

    public function testProductionPasskeyRestTrafficIsAllowedWhenEnabledAndReady(): void
    {
        $listener = $this->createListener('prod', true, true);
        $event = $this->createRequestEvent('/api/passkeys/register/options', Request::METHOD_POST);

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testProductionGraphQlPasskeyTrafficIsBlockedWhenTrafficFlagDisabled(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createPasskeyGraphQlBody($this->faker->safeEmail())
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionRawGraphQlPasskeyTrafficIsBlockedWhenTrafficFlagDisabled(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail())
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionGetGraphQlPasskeyTrafficIsBlockedWhenTrafficFlagDisabled(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_GET,
            query: ['query' => $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail())]
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionGraphQlQueryStringOperationNameIsBlocked(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createMixedGraphQlOperationsBody($this->faker->safeEmail()),
            query: ['operationName' => 'Passkey']
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionNonPasskeyGraphQlTrafficIsAllowed(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createSignInGraphQlBody($this->faker->safeEmail(), 'passWORD1')
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    private function createListener(
        string $appEnv,
        bool $productionTrafficEnabled,
        bool $productionMonitoringReady
    ): PasskeyProductionReadinessListener {
        return new PasskeyProductionReadinessListener(
            $appEnv,
            $productionTrafficEnabled,
            $productionMonitoringReady,
            new ApiRateLimitGraphQlQueryInspector(new ApiRateLimitGraphQlDocumentResolver())
        );
    }

    /**
     * @param array<string, string> $query
     */
    private function createRequestEvent(
        string $path,
        string $method,
        string $content = '',
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        array $query = []
    ): RequestEvent {
        $uri = $query === [] ? $path : sprintf('%s?%s', $path, http_build_query($query));
        $request = Request::create(
            $uri,
            $method,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );

        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType
        );
    }

    private function createPasskeyGraphQlBody(string $email): string
    {
        return json_encode([
            'query' => $this->createRawPasskeyGraphQlMutation($email),
        ], JSON_THROW_ON_ERROR);
    }

    private function createRawPasskeyGraphQlMutation(string $email): string
    {
        return sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL,
            $email
        );
    }

    private function createMixedGraphQlOperationsBody(string $email): string
    {
        return json_encode([
            'query' => sprintf(
                <<<'GRAPHQL'
mutation Decoy {
  signInUser(input: { email: "user@example.test", password: "passWORD1" }) {
    user { accessToken }
  }
}

mutation Passkey {
  passkeySignInOptionsUser(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL,
                $email
            ),
        ], JSON_THROW_ON_ERROR);
    }

    private function createSignInGraphQlBody(string $email, string $password): string
    {
        return json_encode([
            'query' => sprintf(
                <<<'GRAPHQL'
mutation {
  signInUser(input: { email: "%s", password: "%s" }) {
    user { accessToken }
  }
}
GRAPHQL,
                $email,
                $password
            ),
        ], JSON_THROW_ON_ERROR);
    }

    private function assertUnavailableResponse(RequestEvent $event, string $detail): void
    {
        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('/errors/passkey-production-readiness', $payload['type'] ?? null);
        self::assertSame('Service Unavailable', $payload['title'] ?? null);
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $payload['status'] ?? null);
        self::assertSame($detail, $payload['detail'] ?? null);
    }
}
