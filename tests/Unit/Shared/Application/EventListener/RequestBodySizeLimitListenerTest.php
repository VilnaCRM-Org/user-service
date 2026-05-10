<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\RequestBodySizeLimitListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RequestBodySizeLimitListenerTest extends UnitTestCase
{
    private RequestBodySizeLimitListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new RequestBodySizeLimitListener();
    }

    public function testSubRequestIsIgnored(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], str_repeat('x', 65_537));
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->__invoke($event);
        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenContentLengthExceedsLimit(): void
    {
        $request = Request::create('/', 'POST');
        $request->headers->set('Content-Length', '65537');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->expectException(HttpException::class);
        $this->listener->__invoke($event);
    }

    public function testExceptionStatusCodeIs413(): void
    {
        $request = Request::create('/', 'POST');
        $request->headers->set('Content-Length', '65537');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        try {
            $this->listener->__invoke($event);
            $this->fail('Expected HttpException was not thrown');
        } catch (HttpException $e) {
            $this->assertSame(413, $e->getStatusCode());
        }
    }

    public function testContentLengthAtLimitDoesNotThrow(): void
    {
        $request = Request::create('/', 'POST');
        $request->headers->set('Content-Length', '65536');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->__invoke($event);
        $this->addToAssertionCount(1);
    }

    public function testNonNumericContentLengthDoesNotThrow(): void
    {
        $request = Request::create('/', 'POST');
        $request->headers->set('Content-Length', 'abc');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->__invoke($event);
        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenBodyContentExceedsLimit(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], str_repeat('x', 65_537));
        $request->headers->remove('Content-Length');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->expectException(HttpException::class);
        $this->listener->__invoke($event);
    }

    public function testBodyAtLimitDoesNotThrow(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], str_repeat('x', 65_536));
        $request->headers->remove('Content-Length');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->__invoke($event);
        $this->addToAssertionCount(1);
    }

    public function testSmallBodyDoesNotThrow(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], 'small body');
        $request->headers->remove('Content-Length');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->__invoke($event);
        $this->addToAssertionCount(1);
    }

    public function testNullContentLengthFallsBackToBodyCheck(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], str_repeat('x', 65_537));
        $request->headers->remove('Content-Length');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->expectException(HttpException::class);
        $this->listener->__invoke($event);
    }
}
