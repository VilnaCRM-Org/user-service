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

    /**
     * @test
     */
    public function it_rejects_batch_requests_with_400(): void
    {
        $batchRequest = [
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ];

        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($batchRequest)
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        ($this->listener)($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('batch', strtolower($data['detail'] ?? ''));
    }

    /**
     * @test
     */
    public function it_allows_single_graphql_requests(): void
    {
        $singleRequest = ['query' => '{ __typename }'];

        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($singleRequest)
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        ($this->listener)($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * @test
     */
    public function it_ignores_non_graphql_paths(): void
    {
        $batchRequest = [
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ];

        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($batchRequest)
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        ($this->listener)($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * @test
     */
    public function it_ignores_non_post_requests(): void
    {
        $batchRequest = [
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ];

        $request = Request::create(
            '/api/graphql',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($batchRequest)
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        ($this->listener)($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * @test
     */
    public function it_ignores_empty_content(): void
    {
        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        ($this->listener)($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * @test
     */
    public function it_ignores_invalid_json(): void
    {
        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        ($this->listener)($event);

        $this->assertFalse($event->hasResponse());
    }
}
