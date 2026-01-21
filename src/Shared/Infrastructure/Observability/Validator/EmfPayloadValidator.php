<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;

/**
 * Validates EMF payload for key collisions between dimensions and metrics.
 *
 * Following SOLID:
 * - Single Responsibility: Only validates EmfPayload key collisions
 * - Open/Closed: New validation rules can be added without modifying EmfPayload
 */
final readonly class EmfPayloadValidator implements EmfPayloadValidatorInterface
{
    private const string RESERVED_AWS_KEY = '_aws';

    public function validate(EmfPayload $payload): void
    {
        $dimensionKeys = array_keys($payload->dimensionValues()->toAssociativeArray());
        $metricNames = array_keys($payload->metricValues()->toAssociativeArray());

        $this->validateNoDimensionMetricCollision($dimensionKeys, $metricNames);
        $this->validateNoReservedKeyUsed($dimensionKeys, $metricNames);
    }

    /**
     * @param array<int, string> $dimensionKeys
     * @param array<int, string> $metricNames
     */
    private function validateNoDimensionMetricCollision(
        array $dimensionKeys,
        array $metricNames
    ): void {
        $collisions = array_intersect($dimensionKeys, $metricNames);

        if ($collisions !== []) {
            throw EmfKeyCollisionException::dimensionMetricCollision($collisions);
        }
    }

    /**
     * @param array<int, string> $dimensionKeys
     * @param array<int, string> $metricNames
     */
    private function validateNoReservedKeyUsed(array $dimensionKeys, array $metricNames): void
    {
        $allKeys = [...$dimensionKeys, ...$metricNames];

        if (in_array(self::RESERVED_AWS_KEY, $allKeys, true)) {
            throw EmfKeyCollisionException::reservedKeyUsed(self::RESERVED_AWS_KEY);
        }
    }
}
