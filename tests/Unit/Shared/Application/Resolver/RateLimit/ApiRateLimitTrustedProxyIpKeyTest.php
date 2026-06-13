<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

/**
 * Regression tests for issue #316: IP-keyed rate limiters must derive the
 * client IP from the spoof-resistant value resolved by Symfony's trusted-proxy
 * handling, not from a blanket-trusted X-Forwarded-For header.
 *
 * When only the directly-connected proxy hop is trusted (a single proxy CIDR
 * plus the x-forwarded-for header, matching the spoof-resistant configuration
 * of config/packages/framework.yaml), the resolvers must key on the real
 * client IP. A forged X-Forwarded-For sent from an untrusted client must be
 * ignored.
 */
final class ApiRateLimitTrustedProxyIpKeyTest extends RateLimitClientTestCase
{
    /**
     * @var array<int, string>
     */
    private array $previousTrustedProxies = [];

    private int $previousTrustedHeaderSet = 0;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->previousTrustedProxies = Request::getTrustedProxies();
        $this->previousTrustedHeaderSet = Request::getTrustedHeaderSet();
    }

    #[\Override]
    protected function tearDown(): void
    {
        Request::setTrustedProxies(
            $this->previousTrustedProxies,
            $this->previousTrustedHeaderSet
        );

        parent::tearDown();
    }

    public function testSignInIpKeyUsesRealClientIpWhenProxyIsTrusted(): void
    {
        $this->trustDirectlyConnectedProxy();

        $proxyIp = '10.0.0.5';
        $realClientIp = '203.0.113.42';
        $request = $this->createSignInRequest($proxyIp, $realClientIp);

        $result = $this->createAuthTargetResolver()->resolve($request);

        self::assertSame('signin_ip', $result[0]['name']);
        self::assertSame('ip:' . $realClientIp, $result[0]['key']);
    }

    public function testRegistrationIpKeyUsesRealClientIpWhenProxyIsTrusted(): void
    {
        $this->trustDirectlyConnectedProxy();

        $proxyIp = '10.0.0.5';
        $realClientIp = '203.0.113.7';
        $request = $this->createRegistrationRequest($proxyIp, $realClientIp);

        $result = $this->createRequestResolver()->resolveEndpointLimiters($request);

        self::assertSame('registration', $result[0]['name']);
        self::assertSame('ip:' . $realClientIp, $result[0]['key']);
    }

    public function testForgedForwardedForFromUntrustedClientIsIgnored(): void
    {
        $this->trustDirectlyConnectedProxy();

        $untrustedClientIp = '198.51.100.10';
        $spoofedIp = '203.0.113.99';
        $request = $this->createSignInRequest($untrustedClientIp, $spoofedIp);

        $result = $this->createAuthTargetResolver()->resolve($request);

        self::assertSame('ip:' . $untrustedClientIp, $result[0]['key']);
        self::assertNotSame('ip:' . $spoofedIp, $result[0]['key']);
    }

    public function testForwardedForIsIgnoredWhenNoProxyIsTrusted(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);

        $remoteAddr = '10.0.0.5';
        $spoofedIp = '203.0.113.123';
        $request = $this->createSignInRequest($remoteAddr, $spoofedIp);

        $result = $this->createAuthTargetResolver()->resolve($request);

        self::assertSame('ip:' . $remoteAddr, $result[0]['key']);
    }

    private function trustDirectlyConnectedProxy(): void
    {
        Request::setTrustedProxies(['10.0.0.5'], Request::HEADER_X_FORWARDED_FOR);
    }

    private function createSignInRequest(string $remoteAddr, string $forwardedFor): Request
    {
        return Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => $remoteAddr,
                'HTTP_X_FORWARDED_FOR' => $forwardedFor,
            ]
        );
    }

    private function createRegistrationRequest(string $remoteAddr, string $forwardedFor): Request
    {
        return Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => $remoteAddr,
                'HTTP_X_FORWARDED_FOR' => $forwardedFor,
            ]
        );
    }
}
