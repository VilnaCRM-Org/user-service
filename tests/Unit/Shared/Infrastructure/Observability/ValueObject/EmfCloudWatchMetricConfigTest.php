<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\ValueObject\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Tests\Unit\UnitTestCase;

final class EmfCloudWatchMetricConfigTest extends UnitTestCase
{
    public function testReturnsNamespace(): void
    {
        $config = $this->createConfig();

        self::assertSame('TestApp/Metrics', $config->namespace());
    }

    public function testReturnsDimensionKeys(): void
    {
        $config = $this->createConfig();

        $keys = $config->dimensionKeys();

        self::assertSame(['Endpoint', 'Operation'], $keys->all());
    }

    public function testReturnsMetrics(): void
    {
        $config = $this->createConfig();

        $metrics = $config->metrics();

        self::assertCount(1, $metrics);
    }

    public function testWithAddedMetricCreatesNewInstance(): void
    {
        $config = $this->createConfig();
        $newMetric = new EmfMetricDefinition('OrderValue', 'None');

        $updatedConfig = $config->withAddedMetric($newMetric);

        self::assertNotSame($config, $updatedConfig);
        self::assertCount(1, $config->metrics());
        self::assertCount(2, $updatedConfig->metrics());
    }

    public function testSerializesToExpectedStructure(): void
    {
        $config = $this->createConfig();

        $json = $config->jsonSerialize();

        self::assertSame('TestApp/Metrics', $json['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $json['Dimensions']);
        self::assertCount(1, $json['Metrics']);
    }

    private function createConfig(): EmfCloudWatchMetricConfig
    {
        $dimensionKeys = new EmfDimensionKeys('Endpoint', 'Operation');

        return new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection(new EmfMetricDefinition('Test', 'Count'))
        );
    }
}
