<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\Provider\EmfTimestampProvider;
use App\Shared\Infrastructure\Observability\Validator\EmfNamespaceValidatorInterface;
use App\Shared\Infrastructure\Observability\ValueObject\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\ValueObject\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Shared\Infrastructure\Observability\ValueObject\EmfNamespaceValue;

/**
 * Factory for creating EMF AWS metadata objects
 *
 * Follows SOLID principles:
 * - Single Responsibility: Creates only AWS metadata-related objects
 * - Defensive Programming: Validates namespace at construction time (fail fast)
 */
final readonly class EmfAwsMetadataFactory implements EmfAwsMetadataFactoryInterface
{
    public function __construct(
        private string $namespace,
        private EmfTimestampProvider $timestampProvider,
        EmfNamespaceValidatorInterface $namespaceValidator
    ) {
        $namespaceValidator->validate(new EmfNamespaceValue($namespace));
    }

    #[\Override]
    public function createWithMetric(
        EmfDimensionKeys $dimensionKeys,
        EmfMetricDefinition $definition
    ): EmfAwsMetadata {
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            $this->namespace,
            $dimensionKeys,
            new EmfMetricDefinitionCollection($definition)
        );

        return new EmfAwsMetadata(
            $this->timestampProvider->currentTimestamp(),
            $cloudWatchConfig
        );
    }

    #[\Override]
    public function createEmpty(EmfDimensionKeys $dimensionKeys): EmfAwsMetadata
    {
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            $this->namespace,
            $dimensionKeys,
            new EmfMetricDefinitionCollection()
        );

        return new EmfAwsMetadata(
            $this->timestampProvider->currentTimestamp(),
            $cloudWatchConfig
        );
    }
}
