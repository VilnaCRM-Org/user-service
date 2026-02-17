<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\MethodNotAllowedListener;
use App\Shared\Application\Provider\AllowedMethodsProvider;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MethodNotAllowedListenerTest extends UnitTestCase
{
    public function testAllowedMethodDoesNotSetResponse(): void
    {
        /** @psalm-suppress NoValue */
        $provider = $this->createProviderMock(['POST']);
        /** @psalm-suppress UnevaluatedCode */
        $listener = new MethodNotAllowedListener($provider);
        /** @psalm-suppress UnevaluatedCode */
        $event = $this->createMainRequestEvent('POST');
        /** @psalm-suppress UnevaluatedCode */
        $listener($event);
        /** @psalm-suppress UnevaluatedCode */
        $this->assertFalse($event->hasResponse());
    }

    public function testDisallowedMethodSetsProblemJsonResponse(): void
    {
        /** @psalm-suppress NoValue */
        $provider = $this->createProviderMock(['POST']);
        /** @psalm-suppress UnevaluatedCode */
        $listener = new MethodNotAllowedListener($provider);
        /** @psalm-suppress UnevaluatedCode */
        $event = $this->createMainRequestEvent('PUT');
        /** @psalm-suppress UnevaluatedCode */
        $listener($event);
        /** @psalm-suppress UnevaluatedCode */
        $this->assertTrue($event->hasResponse());
        /** @psalm-suppress UnevaluatedCode */
        $this->assertResponseIsMethodNotAllowed($event);
    }

    public function testIgnoresSubRequest(): void
    {
        $provider = $this->createMock(AllowedMethodsProvider::class);
        $provider->expects($this->never())->method('getAllowedMethods');

        $listener = new MethodNotAllowedListener($provider);

        $event = $this->createRequestEvent('PUT', HttpKernelInterface::SUB_REQUEST);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testUntrackedPathIsIgnored(): void
    {
        /** @psalm-suppress NoValue */
        $provider = $this->createProviderMock([], '/api/users');
        /** @psalm-suppress UnevaluatedCode */
        $listener = new MethodNotAllowedListener($provider);
        /** @psalm-suppress UnevaluatedCode */
        $requestType = HttpKernelInterface::MAIN_REQUEST;
        /** @psalm-suppress UnevaluatedCode */
        $event = $this->createRequestEvent('DELETE', $requestType, '/api/users');
        /** @psalm-suppress UnevaluatedCode */
        $listener($event);
        /** @psalm-suppress UnevaluatedCode */
        $this->assertFalse($event->hasResponse());
    }

    /**
     * @param array<int, string> $allowedMethods
     */
    private function createProviderMock(
        array $allowedMethods,
        string $path = '/api/users/batch'
    ): \PHPUnit\Framework\MockObject\MockObject&AllowedMethodsProvider {
        $provider = $this->createMock(AllowedMethodsProvider::class);
        $provider->expects($this->once())
            ->method('getAllowedMethods')
            ->with($path)
            ->willReturn($allowedMethods);

        return $provider;
    }

    /** @psalm-suppress UnusedMethod */
    private function createMainRequestEvent(string $method): RequestEvent
    {
        return $this->createRequestEvent($method, HttpKernelInterface::MAIN_REQUEST);
    }

    private function createRequestEvent(
        string $method,
        int $requestType,
        string $path = '/api/users/batch'
    ): RequestEvent {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($path, $method),
            $requestType
        );
    }

    /** @psalm-suppress UnusedMethod */
    private function assertResponseIsMethodNotAllowed(RequestEvent $event): void
    {
        $response = $event->getResponse();
        $this->assertSame(405, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertEquals('POST', $response->headers->get('Allow'));

        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('/errors/405', $data['type']);
        $this->assertSame(405, $data['status']);
    }
}
