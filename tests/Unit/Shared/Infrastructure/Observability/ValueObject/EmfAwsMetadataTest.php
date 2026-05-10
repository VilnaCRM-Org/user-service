<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\ValueObject\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\ValueObject\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Tests\Unit\UnitTestCase;

final class EmfAwsMetadataTest extends UnitTestCase
{
    public function testReturnsTimestamp(): void
    {
        $metadata = $this->createMetadata();

        self::assertSame(1702425600000, $metadata->timestamp());
    }

    public function testReturnsCloudWatchMetricConfig(): void
    {
        $metadata = $this->createMetadata();

        $config = $metadata->cloudWatchMetricConfig();

        self::assertSame('TestApp/Metrics', $config->namespace());
    }

    public function testWithUpdatedConfigCreatesNewInstance(): void
    {
        $metadata = $this->createMetadata();
        $newConfig = new EmfCloudWatchMetricConfig(
            'NewNamespace',
            new EmfDimensionKeys('Key'),
            new EmfMetricDefinitionCollection()
        );

        $updatedMetadata = $metadata->withUpdatedConfig($newConfig);

        self::assertNotSame($metadata, $updatedMetadata);
        self::assertSame(1702425600000, $updatedMetadata->timestamp());
        self::assertSame('NewNamespace', $updatedMetadata->cloudWatchMetricConfig()->namespace());
    }

    public function testSerializesToExpectedStructure(): void
    {
        $metadata = $this->createMetadata();

        $json = $metadata->jsonSerialize();

        self::assertSame(1702425600000, $json['Timestamp']);
        self::assertArrayHasKey('CloudWatchMetrics', $json);
        self::assertCount(1, $json['CloudWatchMetrics']);
    }

    private function createMetadata(): EmfAwsMetadata
    {
        $dimensionKeys = new EmfDimensionKeys('Endpoint', 'Operation');
        $config = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection(new EmfMetricDefinition('Test', 'Count'))
        );

        return new EmfAwsMetadata(1702425600000, $config);
    }
}
