<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\SecurityHeadersResponseListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SecurityHeadersResponseListenerTest extends UnitTestCase
{
    public function testSetsExpectedHeadersOnMainRequest(): void
    {
        $listener = new SecurityHeadersResponseListener();
        $event = $this->createResponseEvent(HttpKernelInterface::MAIN_REQUEST);

        $listener($event);

        $headers = $event->getResponse()->headers;
        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $headers->get('Strict-Transport-Security')
        );
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));
        $this->assertSame(
            'strict-origin-when-cross-origin',
            $headers->get('Referrer-Policy')
        );
        $this->assertSame(
            "default-src 'none'; frame-ancestors 'none'",
            $headers->get('Content-Security-Policy')
        );
        $this->assertSame(
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            $headers->get('Permissions-Policy')
        );
    }

    public function testRemovesServerHeaderOnMainRequest(): void
    {
        $listener = new SecurityHeadersResponseListener();
        $event = $this->createResponseEvent(HttpKernelInterface::MAIN_REQUEST);
        $event->getResponse()->headers->set('Server', 'nginx');

        $listener($event);

        $this->assertFalse($event->getResponse()->headers->has('Server'));
    }

    public function testIgnoresSubRequest(): void
    {
        $listener = new SecurityHeadersResponseListener();
        $event = $this->createResponseEvent(HttpKernelInterface::SUB_REQUEST);
        $event->getResponse()->headers->set('Server', 'nginx');

        $listener($event);

        $headers = $event->getResponse()->headers;
        $this->assertSame('nginx', $headers->get('Server'));
        $this->assertNull($headers->get('X-Content-Type-Options'));
    }

    private function createResponseEvent(int $requestType): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/api/health', 'GET'),
            $requestType,
            new Response()
        );
    }
}
