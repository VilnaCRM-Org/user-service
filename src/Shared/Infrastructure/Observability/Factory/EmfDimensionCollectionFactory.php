<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidatorInterface;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;

final readonly class EmfDimensionCollectionFactory
{
    public function __construct(
        private EmfDimensionValueValidatorInterface $validator
    ) {
    }

    public function createFromDimensions(
        MetricDimensionsInterface $dimensions
    ): EmfDimensionValueCollection {
        $dimensionValues = [];
        foreach ($dimensions->values() as $dimension) {
            $dimensionValues[] = new EmfDimensionValue(
                $dimension->key(),
                $dimension->value()
            );
        }

        return new EmfDimensionValueCollection($this->validator, ...$dimensionValues);
    }
}
