<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\GraphQLBatchRejectListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * @covers \App\Shared\Application\EventListener\GraphQLBatchRejectListener
 */
final class GraphQLBatchRejectListenerTest extends UnitTestCase
{
    private GraphQLBatchRejectListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new GraphQLBatchRejectListener(
            $this->createJsonSerializer(),
        );
    }

    public function testRejectsBatchRequestsWith400(): void
    {
        $body = json_encode([
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ]);
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'POST', $body);
        ($this->listener)($event);
        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('about:blank', $data['type'] ?? null);
        $this->assertStringContainsString('batch', strtolower($data['detail'] ?? ''));
    }

    public function testRejectsBatchRequestsUsingAssociativeDecodeContext(): void
    {
        $body = json_encode([
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ], JSON_THROW_ON_ERROR);
        $listener = new GraphQLBatchRejectListener(
            $this->createBatchDecodingSerializer($body),
        );
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'POST', $body);

        $listener($event);

        $this->assertBadRequestResponse($event);
    }

    public function testAllowsSingleGraphqlRequests(): void
    {
        $body = json_encode(['query' => '{ __typename }']);
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'POST', $body);
        ($this->listener)($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresNonGraphqlPaths(): void
    {
        $body = json_encode([
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ]);
        $event = $this->createGraphqlRequestEvent('/api/users', 'POST', $body);
        ($this->listener)($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresNonPostRequests(): void
    {
        $body = json_encode([
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ]);
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'GET', $body);
        ($this->listener)($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresEmptyContent(): void
    {
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'POST', '');
        ($this->listener)($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testRejectsBatchRequestAtMaxJsonDepth(): void
    {
        $deepJson = str_repeat('[', 511) . str_repeat(']', 511);
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'POST', $deepJson);
        ($this->listener)($event);
        $this->assertTrue($event->hasResponse());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());
    }

    public function testIgnoresTooDeepJson(): void
    {
        $tooDeepJson = str_repeat('[', 512) . str_repeat(']', 512);
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'POST', $tooDeepJson);
        ($this->listener)($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresInvalidJson(): void
    {
        $event = $this->createGraphqlRequestEvent('/api/graphql', 'POST', 'invalid json');
        ($this->listener)($event);
        $this->assertFalse($event->hasResponse());
    }

    private function createGraphqlRequestEvent(
        string $path,
        string $method,
        string $body
    ): RequestEvent {
        $request = Request::create(
            $path,
            $method,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );

        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    private function createBatchDecodingSerializer(string $body): Serializer
    {
        $serializer = $this->createMock(Serializer::class);
        $serializer
            ->expects($this->once())
            ->method('decode')
            ->with(
                $body,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true]
            )
            ->willReturn([
                ['query' => '{ __typename }'],
                ['query' => '{ __typename }'],
            ]);

        return $serializer;
    }

    private function assertBadRequestResponse(RequestEvent $event): void
    {
        $this->assertTrue($event->hasResponse());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());
    }
}
