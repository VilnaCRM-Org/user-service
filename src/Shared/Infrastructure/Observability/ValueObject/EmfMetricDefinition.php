<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

/**
 * Represents a single metric definition in AWS EMF format
 *
 * Maps to the CloudWatch metric definition:
 * { "Name": "MetricName", "Unit": "Count" }
 */
final readonly class EmfMetricDefinition implements \JsonSerializable
{
    public function __construct(
        private string $name,
        private string $unit
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    /**
     * @return array{Name: string, Unit: string}
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'Name' => $this->name,
            'Unit' => $this->unit,
        ];
    }
}
