<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait MemoryBrowserReuseTrait
{
    private ?TrackedBrowserObjects $pendingBrowserObjects = null;

    protected function runMemoryScenario(string $coverageTarget, callable $scenario): void
    {
        Assert::assertNotSame('', $coverageTarget);

        $scenario();
    }

    protected function repeatSameKernelScenario(
        KernelBrowser $client,
        callable $scenario,
        int $iterations = 2,
    ): void {
        if ($iterations <= 0) {
            throw new \InvalidArgumentException('Iterations must be greater than zero.');
        }

        $kernelId = spl_object_id($client->getKernel());

        for ($iteration = 0; $iteration < $iterations; ++$iteration) {
            $scenario($client, $iteration);
            $this->assertKernelReuse($kernelId, $client);
            $this->flushPendingBrowserObjects();
            $this->resetBrowserState($client);
        }
    }

    protected function trackBrowserObjects(KernelBrowser $client, string $labelPrefix): void
    {
        if ($this->pendingBrowserObjects !== null) {
            $this->pendingBrowserObjects->expectDeallocation($this->getDeallocationChecker());
        }

        $request = $client->getRequest();
        Assert::assertIsObject($request);
        $response = $client->getResponse();
        Assert::assertIsObject($response);

        $this->pendingBrowserObjects = new TrackedBrowserObjects(
            $request,
            $response,
            $labelPrefix,
        );
        $client->getHistory()->clear();
        gc_collect_cycles();
    }

    protected function flushPendingBrowserObjects(): void
    {
        if (!isset($this->client) || $this->pendingBrowserObjects === null) {
            return;
        }

        $this->client->request(
            'GET',
            '/api/health',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => $this->getBrowserFlushUserAgent(),
                'REMOTE_ADDR' => $this->faker->ipv4(),
            ],
        );

        $this->pendingBrowserObjects->expectDeallocation($this->getDeallocationChecker());
        $this->pendingBrowserObjects = null;
        $this->client->getHistory()->clear();
        gc_collect_cycles();
    }

    protected function resetBrowserState(KernelBrowser $client): void
    {
        $client->getHistory()->clear();
        $client->getCookieJar()->clear();
    }

    abstract protected function getBrowserFlushUserAgent(): string;

    private function assertKernelReuse(int $kernelId, KernelBrowser $client): void
    {
        Assert::assertSame(
            $kernelId,
            spl_object_id($client->getKernel()),
            'Kernel was rebooted between memory iterations.',
        );
    }
}
