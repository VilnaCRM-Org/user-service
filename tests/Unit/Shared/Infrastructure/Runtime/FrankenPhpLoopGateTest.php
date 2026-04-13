<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime;

use App\Shared\Infrastructure\Runtime\FrankenPhpLoopGate;
use PHPUnit\Framework\TestCase;

final class FrankenPhpLoopGateTest extends TestCase
{
    public function testKeepRunningTreatsNegativeLoopMaxAsUnlimited(): void
    {
        $loopGate = new FrankenPhpLoopGate(-1);

        self::assertTrue($loopGate->keepRunning(true));
        self::assertTrue($loopGate->keepRunning(true));
    }

    public function testKeepRunningDoesNotTreatZeroLoopMaxAsUnlimited(): void
    {
        $loopGate = new FrankenPhpLoopGate(0);

        self::assertFalse($loopGate->keepRunning(true));
    }

    public function testKeepRunningStopsImmediatelyWhenRequestWasNotHandled(): void
    {
        $loopGate = new FrankenPhpLoopGate(-1);

        self::assertFalse($loopGate->keepRunning(false));
    }
}
