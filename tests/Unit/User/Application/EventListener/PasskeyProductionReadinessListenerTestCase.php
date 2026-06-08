<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventListener;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlDocumentResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspector;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventListener\PasskeyGraphQlRequestResolver;
use App\User\Application\EventListener\PasskeyProductionReadinessListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

abstract class PasskeyProductionReadinessListenerTestCase extends UnitTestCase
{
    protected const PASSKEY_GRAPHQL_FIELDS = [
        'passkeySignUpOptionsUser',
        'passkeySignUpCompleteUser',
        'passkeySignInOptionsUser',
        'passkeySignInCompleteUser',
        'passkeyRegistrationOptionsUser',
        'passkeyRegistrationCompleteUser',
    ];
    protected const TRAFFIC_DISABLED_DETAIL =
        'Passkey production traffic is disabled until production monitoring and alerts are ready.';
    protected const MONITORING_NOT_READY_DETAIL =
        self::MONITORING_NOT_READY_DETAIL_PREFIX . self::MONITORING_NOT_READY_DETAIL_SUFFIX;
    private const MONITORING_NOT_READY_DETAIL_PREFIX =
        'Passkey production traffic requires latency, traffic, error-rate, ';
    private const MONITORING_NOT_READY_DETAIL_SUFFIX =
        'active-challenge, expired-challenge, and TTL-index monitoring before enablement.';

    /**
     * @psalm-return \Generator<string, list{string}, void, void>
     */
    public static function passkeyRestEndpointsProvider(): \Generator
    {
        yield 'signup options' => ['/api/passkeys/signup/options'];
        yield 'signup complete' => ['/api/passkeys/signup/complete'];
        yield 'register options' => ['/api/passkeys/register/options'];
        yield 'register complete' => ['/api/passkeys/register/complete'];
        yield 'signin options' => ['/api/passkeys/signin/options'];
        yield 'signin complete' => ['/api/passkeys/signin/complete'];
    }

    /**
     * @psalm-return \Generator<string, list{string}, void, void>
     */
    public static function similarNonPasskeyRestPathsProvider(): \Generator
    {
        yield 'prefix before passkey path' => ['/internal/api/passkeys/signin/options'];
        yield 'suffix after passkey path' => ['/api/passkeys/signin/options/extra'];
    }

    /**
     * @psalm-return \Generator<string, list{string}, void, void>
     */
    public static function passkeyGraphQlFieldsProvider(): \Generator
    {
        foreach (self::PASSKEY_GRAPHQL_FIELDS as $fieldName) {
            yield $fieldName => [$fieldName];
        }
    }

    protected function createListener(
        string $appEnv,
        bool $productionTrafficEnabled,
        bool $productionMonitoringReady
    ): PasskeyProductionReadinessListener {
        return new PasskeyProductionReadinessListener(
            $appEnv,
            $productionTrafficEnabled,
            $productionMonitoringReady,
            new ApiRateLimitGraphQlQueryInspector(new ApiRateLimitGraphQlDocumentResolver()),
            new PasskeyGraphQlRequestResolver(new JsonEncoder())
        );
    }

    /**
     * @param array<string, string> $query
     * @param array<string, array|scalar|null> $requestParameters
     */
    protected function createRequestEvent(
        string $path,
        string $method,
        string $content = '',
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        array $query = [],
        array $requestParameters = [],
        ?string $contentType = 'application/json'
    ): RequestEvent {
        $uri = $query === [] ? $path : sprintf('%s?%s', $path, http_build_query($query));
        $server = $contentType === null ? [] : ['CONTENT_TYPE' => $contentType];
        $request = Request::create(
            $uri,
            $method,
            $requestParameters,
            [],
            [],
            $server,
            $content
        );

        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType
        );
    }

    protected function createPasskeyGraphQlBody(
        string $email,
        string $fieldName = 'passkeySignInOptionsUser'
    ): string {
        return json_encode([
            'query' => $this->createRawPasskeyGraphQlMutation($email, $fieldName),
        ], JSON_THROW_ON_ERROR);
    }

    protected function createPasskeyGraphQlOperations(
        string $email,
        string $fieldName = 'passkeySignInOptionsUser'
    ): string {
        return $this->createPasskeyGraphQlBody($email, $fieldName);
    }

    protected function createRawPasskeyGraphQlMutation(
        string $email,
        string $fieldName = 'passkeySignInOptionsUser'
    ): string {
        return sprintf(
            <<<'GRAPHQL'
mutation {
  %s(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL,
            $fieldName,
            $email
        );
    }

    protected function createMixedGraphQlOperationsBody(string $email): string
    {
        return json_encode([
            'query' => $this->createMixedGraphQlOperationsQuery($email),
        ], JSON_THROW_ON_ERROR);
    }

    protected function createMixedGraphQlOperationsQuery(string $email): string
    {
        return sprintf(
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
        );
    }

    protected function createSignInGraphQlBody(string $email, string $password): string
    {
        return json_encode([
            'query' => $this->createRawSignInGraphQlMutation($email, $password),
        ], JSON_THROW_ON_ERROR);
    }

    protected function createRawSignInGraphQlMutation(string $email, string $password): string
    {
        return sprintf(
            <<<'GRAPHQL'
mutation {
  signInUser(input: { email: "%s", password: "%s" }) {
    user { accessToken }
  }
}
GRAPHQL,
            $email,
            $password
        );
    }

    protected function assertUnavailableResponse(RequestEvent $event, string $detail): void
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
