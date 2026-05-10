<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Aws\Sqs\SqsClient;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Doctrine\ODM\MongoDB\DocumentManager;
use Faker\Factory;
use Faker\Generator;
use MongoDB\Client;
use MongoDB\Database;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class HealthCheckContext implements Context
{
    private bool $kernelDirty = false;
    private RestContext $restContext;
    private Generator $faker;

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Given the cache is not working
     */
    public function theCacheIsNotWorking(): void
    {
        $failingPool = new class() extends ArrayAdapter {
            /**
             * @return never
             */
            #[\Override]
            public function get(
                string $key,
                callable $callback,
                ?float $beta = null,
                ?array &$metadata = null
            ): array|bool|float|int|object|string|null {
                throw new \RuntimeException('Cache is not working');
            }
        };

        $this->replaceService('cache.app', new TraceableAdapter($failingPool));
    }

    /**
     * @Given the database is not available
     */
    public function theDatabaseIsNotAvailable(): void
    {
        $this->rebootKernelIfNeeded();
        $documentManager = $this->getDocumentManager();

        $failingClient = new class(sprintf('mongodb://%s', $this->faker->ipv4())) extends Client {
            /**
             * @return never
             */
            #[\Override]
            public function selectDatabase(string $databaseName, array $options = []): Database
            {
                throw new \RuntimeException('Database is not available');
            }
        };

        $reflection = new \ReflectionProperty($documentManager, 'client');
        $reflection->setValue($documentManager, $failingClient);
    }

    /**
     * @Given the message broker is not available
     */
    public function theMessageBrokerIsNotAvailable(): void
    {
        $this->replaceService(SqsClient::class, $this->createFailingSqsClient());
    }

    /**
     * @Then print last response
     */
    public function printLastResponse(): void
    {
        echo 'Response content: ' . $this->getResponseContent() . "\n";
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

    private function getResponseContent(): string
    {
        return $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
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
            public function __call($name, array $args): never
            {
                throw new \RuntimeException('Message broker is not available');
            }
        };
    }

    private function replaceService(string $serviceId, object $service): void
    {
        $this->rebootKernelIfNeeded();
        $this->container()->set($serviceId, $service);
    }

    private function rebootKernelIfNeeded(): void
    {
        if ($this->kernelDirty) {
            return;
        }

        $this->kernel->reboot(null);
        $this->kernelDirty = true;
    }

    private function getDocumentManager(): DocumentManager
    {
        $documentManager = $this->container()->get(DocumentManager::class);
        if (!$documentManager instanceof DocumentManager) {
            throw new \RuntimeException('Document manager is not available');
        }

        return $documentManager;
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
