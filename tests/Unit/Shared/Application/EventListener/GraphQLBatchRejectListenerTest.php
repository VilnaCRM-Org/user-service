<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\GraphQLBatchRejectListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers \App\Shared\Application\EventListener\GraphQLBatchRejectListener
 */
final class GraphQLBatchRejectListenerTest extends TestCase
{
    private GraphQLBatchRejectListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new GraphQLBatchRejectListener();
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
}
