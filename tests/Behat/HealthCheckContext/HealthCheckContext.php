<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Aws\Sqs\SqsClient;
use Behat\Behat\Context\Context;
use Doctrine\DBAL\Connection;
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
        $failingConnection = $this->createFailingConnection();
        $this->replaceService(Connection::class, $failingConnection);
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

            public function __call($name, array $args): void
            {
                throw new \RuntimeException('Message broker is not available');
            }
        };
    }

    private function createFailingConnection(): FailingConnection
    {
        $connection = $this->container()->get(Connection::class);
        assert($connection instanceof Connection);
        $params = $connection->getParams();
        $driver = $connection->getDriver();
        $config = $connection->getConfiguration();

        return new FailingConnection($params, $driver, $config);
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
