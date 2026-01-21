<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\ValueObject\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;

/**
 * Interface for creating EMF AWS metadata objects
 */
interface EmfAwsMetadataFactoryInterface
{
    /**
     * Creates AWS metadata with a single metric definition
     */
    public function createWithMetric(
        EmfDimensionKeys $dimensionKeys,
        EmfMetricDefinition $definition
    ): EmfAwsMetadata;

    /**
     * Creates AWS metadata without metric definitions (for collections)
     */
    public function createEmpty(EmfDimensionKeys $dimensionKeys): EmfAwsMetadata;
}
