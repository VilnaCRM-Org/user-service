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
}
