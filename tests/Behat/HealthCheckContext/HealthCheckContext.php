<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Aws\Sqs\SqsClient;
use Behat\Behat\Context\Context;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\HttpKernel\KernelInterface;

final class HealthCheckContext extends KernelTestCase implements Context
{
    private KernelInterface $kernelInterface;
    private bool $kernelDirty = false;

    public function __construct(
        KernelInterface $kernel
    ) {
        parent::__construct();
        $this->kernelInterface = $kernel;
    }

    /**
     * @Given the cache is not working
     */
    public function theCacheIsNotWorking(): void
    {
        $traceableCacheMock = $this->createMock(TraceableAdapter::class);

        $traceableCacheMock->method('get')
            ->willThrowException(new \Exception('Cache is not working'));

        $this->replaceService('cache.app', $traceableCacheMock);
    }

    /**
     * @Given the database is not available
     */
    public function theDatabaseIsNotAvailable(): void
    {
        $driverMock = $this->createMock(Driver::class);

        $connectionMock = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([
                [],
                $driverMock,
                new Configuration(),
                new EventManager(),
            ])
            ->onlyMethods(['executeQuery'])
            ->getMock();

        $connectionMock->method('executeQuery')
            ->willThrowException(new \Exception('Database is not available'));

        $this->replaceService(Connection::class, $connectionMock);
    }

    /**
     * @Given the message broker is not available
     */
    public function theMessageBrokerIsNotAvailable(): void
    {
        $sqsClientMock = $this->createMock(SqsClient::class);

        $sqsClientMock->method('__call')
            ->willThrowException(
                new \Exception(
                    'Message broker is not available'
                )
            );

        $this->replaceService(SqsClient::class, $sqsClientMock);
    }

    /**
     * @AfterScenario
     */
    public function restoreMockedServices(): void
    {
        if ($this->kernelDirty === false) {
            return;
        }

        $this->kernelInterface->reboot(null);
        $this->kernelDirty = false;
    }

    private function replaceService(string $serviceId, object $service): void
    {
        if ($this->kernelDirty === false) {
            $this->kernelInterface->reboot(null);
            $this->kernelDirty = true;
        }

        $container = $this->kernelInterface
            ->getContainer()
            ->get('test.service_container');

        $container->set($serviceId, $service);
    }
}
