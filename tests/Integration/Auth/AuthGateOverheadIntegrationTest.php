<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AuthGateOverheadIntegrationTest extends IntegrationTestCase
{
    private const ITERATIONS = 20;
    private const MAX_AUTH_GATE_OVERHEAD_MS = 5.0;
    private const MAX_AUTH_GATE_OVERHEAD_MS_WITH_COVERAGE = 20.0;

    public function testAuthGateOverheadIsBelowFiveMillisecondsPerRequest(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        $this->performHealthRequest($kernel, false);
        $this->performHealthRequest($kernel, true);

        $anonymousAverageMs = $this->measureAverageLatencyMs($kernel, false);
        $authenticatedAverageMs = $this->measureAverageLatencyMs($kernel, true);
        $overheadMs = $authenticatedAverageMs - $anonymousAverageMs;
        $maxAllowedOverheadMs = $this->resolveMaxAllowedOverheadMs();

        $this->assertLessThan(
            $maxAllowedOverheadMs,
            $overheadMs,
            sprintf(
                'Auth-gate overhead %.3fms exceeds %.1fms (anon=%.3fms, auth=%.3fms).',
                $overheadMs,
                $maxAllowedOverheadMs,
                $anonymousAverageMs,
                $authenticatedAverageMs
            )
        );
    }

    private function measureAverageLatencyMs(
        HttpKernelInterface $kernel,
        bool $withAuthorization
    ): float {
        $totalMs = 0.0;

        for ($iteration = 0; $iteration < self::ITERATIONS; $iteration++) {
            $totalMs += $this->performHealthRequest($kernel, $withAuthorization);
        }

        return $totalMs / self::ITERATIONS;
    }

    private function performHealthRequest(
        HttpKernelInterface $kernel,
        bool $withAuthorization
    ): float {
        $server = ['HTTP_ACCEPT' => 'application/json'];
        if ($withAuthorization) {
            $headers = $this->createAuthenticatedHeaders(
                sprintf('service-%s', strtolower($this->faker->lexify('????'))),
                ['ROLE_SERVICE']
            );
            $server['HTTP_AUTHORIZATION'] = $headers['HTTP_AUTHORIZATION'];
        }

        $request = Request::create('/api/health', 'GET', [], [], [], $server);

        $startedAt = hrtime(true);
        $response = $kernel->handle($request);
        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

        $this->assertContains($response->getStatusCode(), [200, 204]);

        return $elapsedMs;
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
