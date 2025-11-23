<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Aws\Sqs\SqsClient;
use Behat\Behat\Context\Context;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Result;
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
            public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
            {
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
        /** @var Connection $connection */
        $connection = $this->container()->get(Connection::class);

        $failingConnection = new class($connection->getParams(), $connection->getDriver(), $connection->getConfiguration()) extends Connection {
            public function __construct(array $params, Driver $driver, Configuration $config)
            {
                parent::__construct($params, $driver, $config);
            }

            public function executeQuery(
                string $sql,
                array $params = [],
                array $types = [],
                ?QueryCacheProfile $qcp = null
            ): Result {
                throw new \RuntimeException('Database is not available');
            }

            public function executeStatement(string $sql, array $params = [], array $types = []): int
            {
                throw new \RuntimeException('Database is not available');
            }
        };

        $this->replaceService(Connection::class, $failingConnection);
    }

    /**
     * @Given the message broker is not available
     */
    public function theMessageBrokerIsNotAvailable(): void
    {
        $failingSqsClient = new class() extends SqsClient {
            public function __construct()
            {
                parent::__construct([
                    'service' => 'sqs',
                    'version' => 'latest',
                    'region' => 'us-east-1',
                    'credentials' => [
                        'key' => 'invalid',
                        'secret' => 'invalid',
                    ],
                ]);
            }

            public function __call($name, array $arguments): void
            {
                throw new \RuntimeException('Message broker is not available');
            }
        };

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
        /** @var ContainerInterface $container */
        return $this->kernel->getContainer()->get('test.service_container');
    }
}
