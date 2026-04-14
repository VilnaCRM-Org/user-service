<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use LogicException;
use ShipMonk\MemoryScanner\ObjectDeallocationChecker;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetterInterface;

abstract class MemoryLeakAwareWebTestCase extends WebTestCase
{
    private ?ObjectDeallocationChecker $deallocationChecker = null;

    #[\Override]
    protected function tearDown(): void
    {
        if (static::$kernel !== null) {
            $this->trackInitializedServicesForDeallocation();
        }

        parent::tearDown();

        if ($this->deallocationChecker === null) {
            return;
        }

        $deallocationChecker = $this->deallocationChecker;
        $this->deallocationChecker = null;

        $leakCauses = $deallocationChecker->checkDeallocations();
        if ($leakCauses !== []) {
            // @codeCoverageIgnoreStart
            self::fail($deallocationChecker->explainLeaks($leakCauses));
            // @codeCoverageIgnoreEnd
        }
    }

    protected function createMemoryClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        return $client;
    }

    protected function finishMemoryRequestCycle(): void
    {
        static::getContainer()->get(ServicesResetterInterface::class)->reset();
        gc_collect_cycles();
    }

    /**
     * @return list<string>
     */
    protected function getIgnoredServiceLeaks(): array
    {
        return [];
    }

    private function getDeallocationChecker(): ObjectDeallocationChecker
    {
        $this->deallocationChecker ??= new ObjectDeallocationChecker();

        return $this->deallocationChecker;
    }

    private function trackInitializedServicesForDeallocation(): void
    {
        $container = static::$kernel?->getContainer();
        if (!$container instanceof Container) {
            // @codeCoverageIgnoreStart
            throw new LogicException(
                'Container is not an instance of Symfony\Component\DependencyInjection\Container.'
            );
            // @codeCoverageIgnoreEnd
        }

        $ignoredServiceLeaks = $this->getIgnoredServiceLeaks();
        $this->getDeallocationChecker()->expectDeallocation($container, 'container');

        foreach ($container->getServiceIds() as $serviceId) {
            if (
                $container->initialized($serviceId)
                && !\in_array($serviceId, $ignoredServiceLeaks, true)
            ) {
                $service = $container->get($serviceId);
                $this->getDeallocationChecker()->expectDeallocation(
                    $service,
                    sprintf('service %s', $serviceId)
                );
            }
        }
    }
}
