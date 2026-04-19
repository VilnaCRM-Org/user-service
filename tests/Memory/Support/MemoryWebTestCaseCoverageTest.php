<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Container;

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

    public function testGetIgnoredServiceLeaksReturnsEmptyList(): void
    {
        self::assertSame([], $this->getIgnoredServiceLeaks());
    }

    public function testTrackInitializedServiceReturnsWhenContainerResolvesNull(): void
    {
        $container = $this->createMock(Container::class);
        $container
            ->expects(self::once())
            ->method('get')
            ->with('nullable.service')
            ->willReturn(null);

        $trackInitializedService = \Closure::bind(
            function (Container $container): void {
                $this->trackInitializedService($container, 'nullable.service');
            },
            $this,
            MemoryWebTestCase::class,
        );

        self::assertInstanceOf(\Closure::class, $trackInitializedService);

        $trackInitializedService($container);
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
