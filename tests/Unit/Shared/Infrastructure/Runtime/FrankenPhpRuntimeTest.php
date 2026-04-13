<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime;

use App\Shared\Infrastructure\Runtime\FrankenPhpRunner;
use App\Shared\Infrastructure\Runtime\FrankenPhpRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class FrankenPhpRuntimeTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        putenv('FRANKENPHP_WORKER');

        parent::tearDown();
    }

    public function testGetRunnerUsesCustomRunnerOnlyInWorkerMode(): void
    {
        $runtime = new FrankenPhpRuntime();
        $kernel = $this->createStub(HttpKernelInterface::class);

        self::assertNotInstanceOf(FrankenPhpRunner::class, $runtime->getRunner(null));
        self::assertNotInstanceOf(FrankenPhpRunner::class, $runtime->getRunner($kernel));

        putenv('FRANKENPHP_WORKER=1');

        self::assertInstanceOf(FrankenPhpRunner::class, $runtime->getRunner($kernel));
    }

    public function testGetRunnerUsesUnlimitedLoopMaxWhenOptionIsMissing(): void
    {
        putenv('FRANKENPHP_WORKER=1');

        $runtime = new FrankenPhpRuntime();
        $this->setRuntimeOptions($runtime, []);

        $runner = $runtime->getRunner($this->createStub(HttpKernelInterface::class));

        self::assertInstanceOf(FrankenPhpRunner::class, $runner);
        self::assertSame(-1, $this->readRunnerLoopMax($runner));
    }

    public function testGetRunnerCastsConfiguredLoopMaxBeforePassingItToRunner(): void
    {
        putenv('FRANKENPHP_WORKER=1');

        $runtime = new FrankenPhpRuntime(['frankenphp_loop_max' => 1]);
        $this->setRuntimeOptions($runtime, ['frankenphp_loop_max' => '7']);

        $runner = $runtime->getRunner($this->createStub(HttpKernelInterface::class));

        self::assertInstanceOf(FrankenPhpRunner::class, $runner);
        self::assertSame(7, $this->readRunnerLoopMax($runner));
    }

    /**
     * @param array<string, int|string> $options
     */
    private function setRuntimeOptions(FrankenPhpRuntime $runtime, array $options): void
    {
        $reflection = new \ReflectionProperty($runtime, 'options');
        $reflection->setValue($runtime, $options);
    }

    private function readRunnerLoopMax(FrankenPhpRunner $runner): int
    {
        $reflection = new \ReflectionProperty($runner, 'loopMax');

        return $reflection->getValue($runner);
    }
}
