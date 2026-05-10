<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AuthGateOverheadIntegrationTest extends AuthIntegrationTestCase
{
    private const ITERATIONS = 20;
    private const MAX_AUTH_GATE_OVERHEAD_MS = 5.0;
    private const MAX_AUTH_GATE_OVERHEAD_MS_WITH_COVERAGE = 20.0;

    public function testAuthGateOverheadIsBelowFiveMillisecondsPerRequest(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        $anonymousServer = ['HTTP_ACCEPT' => 'application/json'];
        $authenticatedServer = $this->createAuthenticatedServer();

        $this->warmUpHealthRequests($kernel, $anonymousServer, $authenticatedServer);
        $this->assertAuthGateOverheadWithinThreshold(
            $kernel,
            $anonymousServer,
            $authenticatedServer
        );
    }

    /**
     * @param array<string, string> $anonymousServer
     * @param array<string, string> $authenticatedServer
     */
    private function warmUpHealthRequests(
        HttpKernelInterface $kernel,
        array $anonymousServer,
        array $authenticatedServer
    ): void {
        $this->performHealthRequest($kernel, $anonymousServer);
        $this->performHealthRequest($kernel, $authenticatedServer);
    }

    /**
     * @param array<string, string> $anonymousServer
     * @param array<string, string> $authenticatedServer
     */
    private function assertAuthGateOverheadWithinThreshold(
        HttpKernelInterface $kernel,
        array $anonymousServer,
        array $authenticatedServer
    ): void {
        $anonymousMedianMs = $this->measureMedianLatencyMs($kernel, $anonymousServer);
        $authenticatedMedianMs = $this->measureMedianLatencyMs($kernel, $authenticatedServer);
        $overheadMs = $authenticatedMedianMs - $anonymousMedianMs;
        $maxAllowedOverheadMs = $this->resolveMaxAllowedOverheadMs();

        $this->assertLessThan($maxAllowedOverheadMs, $overheadMs, sprintf(
            'Auth-gate median overhead %.3fms exceeds %.1fms (anon=%.3fms, auth=%.3fms).',
            $overheadMs,
            $maxAllowedOverheadMs,
            $anonymousMedianMs,
            $authenticatedMedianMs
        ));
    }

    /**
     * @param array<string, string> $server
     */
    private function measureMedianLatencyMs(
        HttpKernelInterface $kernel,
        array $server
    ): float {
        $latenciesMs = [];

        for ($iteration = 0; $iteration < self::ITERATIONS; $iteration++) {
            $latenciesMs[] = $this->performHealthRequest($kernel, $server);
        }

        sort($latenciesMs);
        $count = count($latenciesMs);
        $midpoint = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($latenciesMs[$midpoint - 1] + $latenciesMs[$midpoint]) / 2;
        }

        return $latenciesMs[$midpoint];
    }

    /**
     * @param array<string, string> $server
     */
    private function performHealthRequest(
        HttpKernelInterface $kernel,
        array $server
    ): float {
        $request = Request::create('/api/health', 'GET', [], [], [], $server);

        $startedAt = hrtime(true);
        $response = $kernel->handle($request);
        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

        $this->assertContains($response->getStatusCode(), [200, 204]);

        return $elapsedMs;
    }

    /**
     * @return array{HTTP_AUTHORIZATION: string, HTTP_ACCEPT: 'application/json'}
     */
    private function createAuthenticatedServer(): array
    {
        return $this->createAuthenticatedHeaders(
            sprintf('service-%s', strtolower($this->faker->lexify('????'))),
            ['ROLE_SERVICE']
        );
    }

    private function resolveMaxAllowedOverheadMs(): float
    {
        $xdebugMode = getenv('XDEBUG_MODE');
        if (is_string($xdebugMode) && str_contains($xdebugMode, 'coverage')) {
            return self::MAX_AUTH_GATE_OVERHEAD_MS_WITH_COVERAGE;
        }

        return self::MAX_AUTH_GATE_OVERHEAD_MS;
    }
}
