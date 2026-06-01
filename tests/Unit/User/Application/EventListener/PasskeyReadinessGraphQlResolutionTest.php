<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class PasskeyReadinessGraphQlResolutionTest extends PasskeyProductionReadinessListenerTestCase
{
    public function testProductionGetGraphQlRawBodyUsesQueryString(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_GET,
            $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            query: [
                'query' => $this->createRawSignInGraphQlMutation(
                    $this->faker->safeEmail(),
                    'passWORD1'
                ),
            ],
            contentType: 'application/graphql'
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testProductionRawGraphQlPasskeyTrafficChecksMimeTypeFallback(): void
    {
        $this->assertRawGraphQlPasskeyTrafficUsesRequestContent(
            'application/graphql',
            'application/vnd.graphql'
        );
    }

    public function testProductionRawGraphQlPasskeyTrafficChecksMimeTypeParameters(): void
    {
        $this->assertRawGraphQlPasskeyTrafficUsesRequestContent(
            'application/graphql; charset=utf-8',
            'application/vnd.graphql'
        );
    }

    public function testProductionRawGraphQlPasskeyTrafficChecksUppercaseMimeTypeFallback(): void
    {
        $this->assertRawGraphQlPasskeyTrafficUsesRequestContent(
            'APPLICATION/GRAPHQL',
            'application/vnd.graphql'
        );
    }

    public function testProductionRawGraphQlPasskeyTrafficUsesGraphQlRequestFormat(): void
    {
        $this->assertRawGraphQlPasskeyTrafficUsesRequestContent(
            'application/x-graphql-test',
            'application/x-graphql-test'
        );
    }

    public function testProductionGraphQlBlankJsonQueryFallsBackToQueryString(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            json_encode(['query' => '   '], JSON_THROW_ON_ERROR),
            query: [
                'query' => $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            ]
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionGraphQlBlankQueryStringFallsBackToBody(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            query: ['query' => '   '],
            contentType: 'text/plain'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionGraphQlPostWithoutContentTypeUsesQueryString(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            query: [
                'query' => $this->createRawSignInGraphQlMutation(
                    $this->faker->safeEmail(),
                    'passWORD1'
                ),
            ],
            contentType: null
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testProductionGraphQlBlankOperationNameUsesFirstOperation(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            json_encode([
                'query' => $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
                'operationName' => '   ',
            ], JSON_THROW_ON_ERROR)
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionFormOperationsOperationNameSelectsQueryStringOperation(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            query: [
                'query' => $this->createMixedGraphQlOperationsQuery($this->faker->safeEmail()),
            ],
            requestParameters: [
                'operations' => json_encode(['operationName' => 'Passkey'], JSON_THROW_ON_ERROR),
            ],
            contentType: 'application/x-www-form-urlencoded'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionMalformedFormGraphQlOperationsFallsBackToQueryString(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            query: [
                'query' => $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            ],
            requestParameters: ['operations' => '{'],
            contentType: 'application/x-www-form-urlencoded'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionJsonRequestIgnoresGraphQlOperationsRequestParameter(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            query: [
                'query' => $this->createRawSignInGraphQlMutation(
                    $this->faker->safeEmail(),
                    'passWORD1'
                ),
            ],
            requestParameters: [
                'operations' => $this->createPasskeyGraphQlOperations(
                    $this->faker->safeEmail()
                ),
            ]
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testProductionFormGraphQlArrayOperationsFallsBackToQueryString(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            query: [
                'query' => $this->createRawSignInGraphQlMutation(
                    $this->faker->safeEmail(),
                    'passWORD1'
                ),
            ],
            requestParameters: [
                'operations' => ['query' => $this->createRawPasskeyGraphQlMutation(
                    $this->faker->safeEmail()
                ),
                ],
            ],
            contentType: 'application/x-www-form-urlencoded'
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testProductionScalarFormGraphQlOperationsFallsBackToQueryString(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            query: [
                'query' => $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            ],
            requestParameters: ['operations' => 'true'],
            contentType: 'application/x-www-form-urlencoded'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionBlankFormGraphQlOperationsFallsBackToQueryString(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            query: [
                'query' => $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            ],
            requestParameters: ['operations' => '   '],
            contentType: 'application/x-www-form-urlencoded'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionPasskeyGraphQlPayloadOnOtherPathIsAllowed(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/users',
            Request::METHOD_POST,
            $this->createPasskeyGraphQlBody($this->faker->safeEmail())
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testProductionPlainTextGraphQlBodyFallsBackToContent(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            contentType: 'text/plain'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionEmptyGraphQlRequestIsAllowed(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent('/api/graphql', Request::METHOD_POST);

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    private function assertRawGraphQlPasskeyTrafficUsesRequestContent(
        string $contentType,
        string $registeredGraphQlMimeType
    ): void {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRawGraphQlPasskeyEventWithDecoyQuery($contentType);
        $request = $event->getRequest();
        $request->setFormat('graphql', [$registeredGraphQlMimeType]);

        try {
            $listener($event);
        } finally {
            $request->setFormat('graphql', ['application/graphql']);
        }

        $this->assertUnavailableResponse($event, self::TRAFFIC_DISABLED_DETAIL);
    }

    private function createRawGraphQlPasskeyEventWithDecoyQuery(string $contentType): RequestEvent
    {
        return $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            query: [
                'query' => $this->createRawSignInGraphQlMutation(
                    $this->faker->safeEmail(),
                    'passWORD1'
                ),
            ],
            contentType: $contentType
        );
    }
}
