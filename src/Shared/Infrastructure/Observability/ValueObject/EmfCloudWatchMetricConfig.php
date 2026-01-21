<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;

/**
 * Represents CloudWatch metric configuration in EMF format
 *
 * Maps to:
 * {
 *   "Namespace": "MyApp/Metrics",
 *   "Dimensions": [["Endpoint", "Operation"]],
 *   "Metrics": [{"Name": "MetricName", "Unit": "Count"}]
 * }
 */
final readonly class EmfCloudWatchMetricConfig implements \JsonSerializable
{
    public function __construct(
        private string $namespace,
        private EmfDimensionKeys $dimensionKeys,
        private EmfMetricDefinitionCollection $metrics
    ) {
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public function dimensionKeys(): EmfDimensionKeys
    {
        return $this->dimensionKeys;
    }

    public function metrics(): EmfMetricDefinitionCollection
    {
        return $this->metrics;
    }

    public function withAddedMetric(EmfMetricDefinition $metric): self
    {
        return new self(
            $this->namespace,
            $this->dimensionKeys,
            $this->metrics->add($metric)
        );
    }

    /**
     * @return array{Namespace: string, Dimensions: array<int, array<int, string>>, Metrics: array<int, array{Name: string, Unit: string}>}
     */
    public function jsonSerialize(): array
    {
        return [
            'Namespace' => $this->namespace,
            'Dimensions' => $this->dimensionKeys->jsonSerialize(),
            'Metrics' => $this->metrics->jsonSerialize(),
        ];
    }
}
