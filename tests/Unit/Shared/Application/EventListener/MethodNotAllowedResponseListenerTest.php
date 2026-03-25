<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\MethodNotAllowedResponseListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MethodNotAllowedResponseListenerTest extends UnitTestCase
{
    public function testMethodNotAllowedJsonResponseUsesProblemJsonContentType(): void
    {
        $listener = new MethodNotAllowedResponseListener();
        $event = $this->createResponseEvent(
            '/api/signin',
            'GET',
            Response::HTTP_METHOD_NOT_ALLOWED,
            'Method Not Allowed'
        );

        $listener($event);

        $this->assertSame(
            'application/problem+json',
            $event->getResponse()->headers->get('Content-Type')
        );
    }

    public function testRequestEntityTooLargeJsonResponseUsesProblemJsonContentType(): void
    {
        $listener = new MethodNotAllowedResponseListener();
        $event = $this->createResponseEvent(
            '/api/users',
            'POST',
            Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
            'Content Too Large'
        );

        $listener($event);

        $this->assertSame(
            'application/problem+json',
            $event->getResponse()->headers->get('Content-Type')
        );
    }

    public function testNon405ResponseIsNotChanged(): void
    {
        $listener = new MethodNotAllowedResponseListener();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/api/signin', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(
                '{"status":200}',
                Response::HTTP_OK,
                ['Content-Type' => 'application/json']
            )
        );

        $listener($event);

        $this->assertSame(
            'application/json',
            $event->getResponse()->headers->get('Content-Type')
        );
    }

    private function createResponseEvent(
        string $path,
        string $method,
        int $statusCode,
        string $detail
    ): ResponseEvent {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($path, $method),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(
                $this->jsonResponseBody($statusCode, $detail),
                $statusCode,
                ['Content-Type' => 'application/json']
            )
        );
    }

    private function jsonResponseBody(int $statusCode, string $detail): string
    {
        return json_encode(
            [
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'An error occurred',
                'status' => $statusCode,
                'detail' => $detail,
            ],
            JSON_THROW_ON_ERROR
        );
    }
}
