<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Exception;

final class EmfKeyCollisionException extends \InvalidArgumentException
{
    /**
     * @param array<int, string> $collisions
     */
    public static function dimensionMetricCollision(array $collisions): self
    {
        return new self(sprintf(
            'Key collision detected between dimensions and metrics: %s',
            implode(', ', $collisions)
        ));
    }

    /**
     * @param array<int, string> $duplicateKeys
     */
    public static function duplicateDimensionKeys(array $duplicateKeys): self
    {
        return new self(sprintf(
            'Duplicate dimension keys detected: %s',
            implode(', ', $duplicateKeys)
        ));
    }

    /**
     * @param array<int, string> $duplicateNames
     */
    public static function duplicateMetricNames(array $duplicateNames): self
    {
        return new self(sprintf(
            'Duplicate metric names detected: %s',
            implode(', ', $duplicateNames)
        ));
    }

    public static function reservedKeyUsed(string $key): self
    {
        return new self(sprintf(
            'Key "%s" is reserved for metadata and cannot be used as a dimension or metric name',
            $key
        ));
    }
}
