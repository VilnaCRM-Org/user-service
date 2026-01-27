<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Aws\Sqs\SqsClient;
use Behat\Behat\Context\Context;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class HealthCheckContext implements Context
{
    private bool $kernelDirty = false;
    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Given the cache is not working
     */
    public function theCacheIsNotWorking(): void
    {
        $failingPool = new class() extends ArrayAdapter {
            #[\Override]
            public function get(
                string $key,
                callable $callback,
                ?float $beta = null,
                ?array &$metadata = null
            ): mixed {
                throw new \RuntimeException('Cache is not working');
            }
        };

        $failingCache = new TraceableAdapter($failingPool);

        $this->replaceService('cache.app', $failingCache);
    }

    /**
     * @Given the database is not available
     */
    public function theDatabaseIsNotAvailable(): void
    {
        if ($this->kernelDirty === false) {
            $this->kernel->reboot(null);
            $this->kernelDirty = true;
        }

        $documentManager = $this->container()->get(DocumentManager::class);
        if (!$documentManager instanceof DocumentManager) {
            throw new \RuntimeException('Document manager is not available');
        }

        $failingClient = new class() extends Client {
            public function __construct()
            {
                parent::__construct('mongodb://127.0.0.1');
            }

            #[\Override]
            public function selectDatabase(string $databaseName, array $options = []): void
            {
                throw new \RuntimeException('Database is not available');
            }
        };

        $reflection = new \ReflectionProperty($documentManager, 'client');
        $reflection->setAccessible(true);
        $reflection->setValue($documentManager, $failingClient);
    }

    /**
     * @Given the message broker is not available
     */
    public function theMessageBrokerIsNotAvailable(): void
    {
        $failingSqsClient = $this->createFailingSqsClient();
        $this->replaceService(SqsClient::class, $failingSqsClient);
    }

    /**
     * @AfterScenario
     */
    public function restoreMockedServices(): void
    {
        if ($this->kernelDirty === false) {
            return;
        }

        $this->kernel->reboot(null);
        $this->kernelDirty = false;
    }

    private function createFailingSqsClient(): SqsClient
    {
        return new class() extends SqsClient {
            public function __construct()
            {
                parent::__construct([
                    'service' => 'sqs',
                    'version' => 'latest',
                    'region' => 'us-east-1',
                    'credentials' => ['key' => 'invalid', 'secret' => 'invalid'],
                ]);
            }

            #[\Override]
            public function __call($name, array $args): void
            {
                throw new \RuntimeException('Message broker is not available');
            }
        };
    }

    private function replaceService(string $serviceId, object $service): void
    {
        if ($this->kernelDirty === false) {
            $this->kernel->reboot(null);
            $this->kernelDirty = true;
        }

        $this->container()->set($serviceId, $service);
    }

    private function container(): ContainerInterface
    {
        $container = $this->kernel->getContainer()->get('test.service_container');

        if (!$container instanceof ContainerInterface) {
            throw new \RuntimeException('Test container is not available');
        }

        return $container;
    }
}
