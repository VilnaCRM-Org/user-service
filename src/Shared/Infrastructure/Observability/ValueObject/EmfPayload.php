<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;

/**
 * Complete EMF payload structure for AWS CloudWatch
 *
 * Combines _aws metadata, dimension values, and metric values into
 * a JSON-serializable object that can be logged for CloudWatch ingestion.
 */
final readonly class EmfPayload implements \JsonSerializable
{
    public function __construct(
        private EmfAwsMetadata $awsMetadata,
        private EmfDimensionValueCollection $dimensionValues,
        private EmfMetricValueCollection $metricValues
    ) {
    }

    public function awsMetadata(): EmfAwsMetadata
    {
        return $this->awsMetadata;
    }

    public function dimensionValues(): EmfDimensionValueCollection
    {
        return $this->dimensionValues;
    }

    public function metricValues(): EmfMetricValueCollection
    {
        return $this->metricValues;
    }

    public function withAddedMetric(
        EmfMetricDefinition $definition,
        EmfMetricValue $value
    ): self {
        $updatedConfig = $this->awsMetadata
            ->cloudWatchMetricConfig()
            ->withAddedMetric($definition);

        return new self(
            $this->awsMetadata->withUpdatedConfig($updatedConfig),
            $this->dimensionValues,
            $this->metricValues->add($value)
        );
    }

    /**
     * @return array<string, string|int|float|array<string, int|array<int, array<string, string|array<int, array<int, string>|array<int, array<string, string>>>>>>>
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            ['_aws' => $this->awsMetadata->jsonSerialize()],
            $this->dimensionValues->toAssociativeArray(),
            $this->metricValues->toAssociativeArray()
        );
    }
}
