<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\HttpRequestContextResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class HttpRequestContextResolverTest extends UnitTestCase
{
    private RequestStack&MockObject $requestStack;
    private HttpRequestContextResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->resolver = new HttpRequestContextResolver($this->requestStack);
    }

    public function testResolveRequestReturnsContextRequestWhenItIsARequest(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $this->assertSame($request, $this->resolver->resolveRequest($request));
    }

    public function testResolveRequestFallsBackToRequestStackWhenContextIsNotRequest(): void
    {
        $stackRequest = $this->createMock(Request::class);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($stackRequest);

        $this->assertSame($stackRequest, $this->resolver->resolveRequest(null));
    }

    public function testResolveIpAddressReturnsClientIpFromRequest(): void
    {
        $ipAddress = $this->faker->ipv4();
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn($ipAddress);

        $this->assertSame($ipAddress, $this->resolver->resolveIpAddress($request));
    }

    public function testResolveIpAddressReturnsEmptyStringWhenRequestIsNull(): void
    {
        $this->assertSame('', $this->resolver->resolveIpAddress(null));
    }

    public function testResolveUserAgentReturnsUserAgentHeaderFromRequest(): void
    {
        $userAgent = $this->faker->userAgent();
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => $userAgent]);

        $this->assertSame($userAgent, $this->resolver->resolveUserAgent($request));
    }

    public function testResolveUserAgentReturnsEmptyStringWhenRequestIsNull(): void
    {
        $this->assertSame('', $this->resolver->resolveUserAgent(null));
    }
}
