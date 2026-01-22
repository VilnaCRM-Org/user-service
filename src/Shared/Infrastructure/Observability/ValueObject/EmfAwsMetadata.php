<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

/**
 * Represents the _aws metadata section in EMF format
 *
 * Maps to:
 * {
 *   "Timestamp": 1234567890123,
 *   "CloudWatchMetrics": [...]
 * }
 */
final readonly class EmfAwsMetadata implements \JsonSerializable
{
    public function __construct(
        private int $timestamp,
        private EmfCloudWatchMetricConfig $cloudWatchMetricConfig
    ) {
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public function cloudWatchMetricConfig(): EmfCloudWatchMetricConfig
    {
        return $this->cloudWatchMetricConfig;
    }

    public function withUpdatedConfig(EmfCloudWatchMetricConfig $config): self
    {
        return new self($this->timestamp, $config);
    }

    /**
     * @return array{Timestamp: int, CloudWatchMetrics: array<int, array{Namespace: string, Dimensions: array<int, array<int, string>>, Metrics: array<int, array{Name: string, Unit: string}>}>}
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'Timestamp' => $this->timestamp,
            'CloudWatchMetrics' => [$this->cloudWatchMetricConfig->jsonSerialize()],
        ];
    }
}
