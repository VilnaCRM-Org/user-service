<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventListener;

use Symfony\Component\HttpFoundation\Request;

final class PasskeyReadinessGraphQlBlockingTest extends PasskeyProductionReadinessListenerTestCase
{
    /**
     * @dataProvider passkeyGraphQlFieldsProvider
     */
    public function testProductionGraphQlPasskeyTrafficIsBlockedWhenTrafficFlagDisabled(
        string $fieldName
    ): void {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createPasskeyGraphQlBody($this->faker->safeEmail(), $fieldName)
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    /**
     * @dataProvider passkeyGraphQlFieldsProvider
     */
    public function testProductionFormGraphQlPasskeyTrafficIsBlockedWhenTrafficFlagDisabled(
        string $fieldName
    ): void {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            requestParameters: [
                'operations' => $this->createPasskeyGraphQlOperations(
                    $this->faker->safeEmail(),
                    $fieldName
                ),
                'map' => '{}',
            ],
            contentType: 'application/x-www-form-urlencoded'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    /**
     * @dataProvider passkeyGraphQlFieldsProvider
     */
    public function testProductionMultipartGraphQlPasskeyTrafficIsBlockedWhenTrafficFlagDisabled(
        string $fieldName
    ): void {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_POST,
            requestParameters: [
                'operations' => $this->createPasskeyGraphQlOperations(
                    $this->faker->safeEmail(),
                    $fieldName
                ),
                'map' => '{}',
            ],
            contentType: 'multipart/form-data'
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
            $this->createRawPasskeyGraphQlMutation($this->faker->safeEmail()),
            contentType: 'application/graphql'
        );

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    public function testProductionRawGraphQlPasskeyTrafficIgnoresDecoyQueryString(): void
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
            contentType: 'application/graphql'
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

    public function testProductionGetGraphQlPasskeyQueryIgnoresDecoyJsonBody(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_GET,
            $this->createSignInGraphQlBody($this->faker->safeEmail(), 'passWORD1'),
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

    public function testProductionGetGraphQlOperationNameIgnoresDecoyJsonBody(): void
    {
        $listener = $this->createListener('prod', false, false);
        $query = $this->createMixedGraphQlOperationsQuery($this->faker->safeEmail());
        $event = $this->createRequestEvent(
            '/api/graphql',
            Request::METHOD_GET,
            json_encode([
                'query' => $query,
                'operationName' => 'Decoy',
            ], JSON_THROW_ON_ERROR),
            query: [
                'query' => $query,
                'operationName' => 'Passkey',
            ]
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
}
