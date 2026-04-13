<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use PHPUnit\Framework\Attributes\Group;

#[Group('memory')]
final class MemoryWebTestCaseCoverageTest extends MemoryWebTestCase
{
    public function testTrackKernelServicesForDeallocationReturnsWithoutBootedKernel(): void
    {
        self::ensureKernelShutdown();

        $this->trackKernelServicesForDeallocation();

        self::assertTrue(true);
    }

    public function testAssertTrackedObjectsAreDeallocatedReturnsWhenNothingWasTracked(): void
    {
        $this->assertTrackedObjectsAreDeallocated();

        self::assertTrue(true);
    }

    /**
     * @return list<string>
     */
    #[\Override]
    protected function getIgnoredServiceLeaks(): array
    {
        return [];
    }
}
