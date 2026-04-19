<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use PHPUnit\Framework\Assert;
use ShipMonk\MemoryScanner\ObjectDeallocationChecker;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;

abstract class MemoryWebTestCase extends WebTestCase
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
        if (!$this->canTrackKernelServices()) {
            return;
        }

        $container = static::$kernel->getContainer();
        $ignoredServiceLeaks = $this->getIgnoredServiceLeaks();

        Assert::assertInstanceOf(Container::class, $container);

        foreach ($container->getServiceIds() as $serviceId) {
            if (!$container->initialized($serviceId)) {
                continue;
            }

            if (\in_array($serviceId, $ignoredServiceLeaks, true)) {
                continue;
            }

            $this->trackInitializedService($container, $serviceId);
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

    private function canTrackKernelServices(): bool
    {
        return static::$kernel !== null && $this->status()->isSuccess();
    }

    private function trackInitializedService(Container $container, string $serviceId): void
    {
        $service = $container->get($serviceId);
        if (!is_object($service)) {
            return;
        }

        $this->trackDeallocation($service, sprintf('service %s', $serviceId));
    }

    private function trackDeallocation(object $object, string $label): void
    {
        $this->getDeallocationChecker()->expectDeallocation($object, $label);
    }
}
