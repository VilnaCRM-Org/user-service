<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use function in_array;

use PHPUnit\Framework\Assert;
use ShipMonk\MemoryScanner\ObjectDeallocationChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @phpstan-require-extends KernelTestCase
 * @mixin KernelTestCase
 */
trait CompatibleObjectDeallocationCheckerKernelTestCaseTrait
{
    private ?ObjectDeallocationChecker $deallocationChecker = null;

    protected function getDeallocationChecker(): ObjectDeallocationChecker
    {
        $this->deallocationChecker ??= new ObjectDeallocationChecker();

        return $this->deallocationChecker;
    }

    /**
     * @return list<string>
     */
    abstract protected function getIgnoredServiceLeaks(): array;

    protected function trackKernelServicesForDeallocation(): void
    {
        if (static::$kernel === null || !$this->status()->isSuccess()) {
            return;
        }

        $container = static::$kernel->getContainer();
        $ignoredServiceLeaks = $this->getIgnoredServiceLeaks();

        Assert::assertInstanceOf(Container::class, $container);
        $this->getDeallocationChecker()->expectDeallocation($container, 'container');

        foreach ($container->getServiceIds() as $serviceId) {
            if ($container->initialized($serviceId) && !in_array($serviceId, $ignoredServiceLeaks, true)) {
                $service = $container->get($serviceId);
                if (!is_object($service)) {
                    continue;
                }

                $this->getDeallocationChecker()->expectDeallocation($service, "service {$serviceId}");
            }
        }
    }

    protected function assertTrackedObjectsAreDeallocated(): void
    {
        if ($this->deallocationChecker === null) {
            return;
        }

        $deallocationChecker = $this->deallocationChecker;
        $this->deallocationChecker = null;

        $leakCauses = $deallocationChecker->checkDeallocations();

        Assert::assertCount(0, $leakCauses, $deallocationChecker->explainLeaks($leakCauses));
    }
}
