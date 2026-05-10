<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Validator\EmfPayloadValidatorInterface;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use InvalidArgumentException;

/**
 * Factory for creating EMF payload objects from business metrics
 *
 * Follows SOLID principles:
 * - Single Responsibility: Creates payload objects, delegates metadata to EmfAwsMetadataFactory
 * - Dependency Inversion: Depends on abstractions (validators, metadata factory)
 */
final readonly class EmfPayloadFactory implements EmfPayloadFactoryInterface
{
    public function __construct(
        private EmfAwsMetadataFactoryInterface $metadataFactory,
        private EmfDimensionCollectionFactory $dimensionFactory,
        private EmfMetricFactory $metricFactory,
        private EmfPayloadValidatorInterface $payloadValidator
    ) {
    }

    #[\Override]
    public function createFromMetric(BusinessMetric $metric): EmfPayload
    {
        $dimensionValueCollection = $this->dimensionFactory->createFromDimensions(
            $metric->dimensions()
        );
        $metricDefinition = $this->metricFactory->createDefinition($metric);
        $awsMetadata = $this->metadataFactory->createWithMetric(
            $dimensionValueCollection->keys(),
            $metricDefinition
        );

        $payload = new EmfPayload(
            $awsMetadata,
            $dimensionValueCollection,
            new EmfMetricValueCollection($this->metricFactory->createValue($metric))
        );

        $this->payloadValidator->validate($payload);

        return $payload;
    }

    #[\Override]
    public function createFromCollection(MetricCollection $metrics): EmfPayload
    {
        if ($metrics->isEmpty()) {
            throw new InvalidArgumentException(
                'Cannot create EMF payload from empty metric collection'
            );
        }

        $allMetrics = $metrics->all();
        $dimensions = $this->dimensionFactory->createFromDimensions(
            $allMetrics[0]->dimensions()
        );
        $awsMetadata = $this->metadataFactory->createEmpty($dimensions->keys());
        $payload = new EmfPayload($awsMetadata, $dimensions, new EmfMetricValueCollection());
        $payload = $this->addMetricsToPayload($payload, $allMetrics);

        $this->payloadValidator->validate($payload);

        return $payload;
    }

    /**
     * @param array<int, BusinessMetric> $metrics
     */
    private function addMetricsToPayload(EmfPayload $payload, array $metrics): EmfPayload
    {
        foreach ($metrics as $metric) {
            $payload = $payload->withAddedMetric(
                $this->metricFactory->createDefinition($metric),
                $this->metricFactory->createValue($metric)
            );
        }

        return $payload;
    }
}
