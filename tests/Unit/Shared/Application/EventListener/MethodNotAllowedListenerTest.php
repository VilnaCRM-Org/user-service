<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\MethodNotAllowedListener;
use App\Shared\Application\Provider\Http\AllowedMethodsProvider;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MethodNotAllowedListenerTest extends UnitTestCase
{
    public function testAllowedMethodDoesNotSetResponse(): void
    {
        $provider = $this->createMock(AllowedMethodsProvider::class);
        $provider->expects($this->once())
            ->method('getAllowedMethods')
            ->with('/api/users/batch')
            ->willReturn(['POST']);

        $listener = new MethodNotAllowedListener($provider);
        $request = Request::create('/api/users/batch', 'POST');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDisallowedMethodSetsProblemJsonResponse(): void
    {
        $provider = $this->createMock(AllowedMethodsProvider::class);
        $provider->expects($this->once())
            ->method('getAllowedMethods')
            ->with('/api/users/batch')
            ->willReturn(['POST']);

        $listener = new MethodNotAllowedListener($provider);
        $request = Request::create('/api/users/batch', 'PUT');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertSame(405, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertEquals('POST', $response->headers->get('Allow'));

        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('/errors/405', $data['type']);
        $this->assertSame(405, $data['status']);
    }

    public function testIgnoresSubRequest(): void
    {
        $provider = $this->createMock(AllowedMethodsProvider::class);
        $provider->expects($this->never())
            ->method('getAllowedMethods');

        $listener = new MethodNotAllowedListener($provider);
        $request = Request::create('/api/users/batch', 'PUT');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testUntrackedPathIsIgnored(): void
    {
        $provider = $this->createMock(AllowedMethodsProvider::class);
        $provider->expects($this->once())
            ->method('getAllowedMethods')
            ->with('/api/users')
            ->willReturn([]);

        $listener = new MethodNotAllowedListener($provider);
        $request = Request::create('/api/users', 'DELETE');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }
}
