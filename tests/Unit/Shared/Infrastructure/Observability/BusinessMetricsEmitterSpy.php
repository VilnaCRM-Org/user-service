<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;

final class BusinessMetricsEmitterSpy implements BusinessMetricsEmitterInterface
{
    /** @var array<int, BusinessMetric> */
    private array $emitted = [];
    private bool $shouldFail = false;

    public function emit(BusinessMetric $metric): void
    {
        if ($this->shouldFail) {
            $this->shouldFail = false;
            throw new \RuntimeException('Metric emission failed');
        }
        $this->emitted[] = $metric;
    }

    public function failOnNextCall(): void
    {
        $this->shouldFail = true;
    }

    public function emitCollection(MetricCollection $metrics): void
    {
        foreach ($metrics as $metric) {
            $this->emit($metric);
        }
    }

    public function clear(): void
    {
        $this->emitted = [];
    }

    public function count(): int
    {
        return count($this->emitted);
    }

    public function emitted(): MetricCollection
    {
        return new MetricCollection(...$this->emitted);
    }

    public function assertEmittedWithDimensions(string $metricName, MetricDimension ...$dimensions): void
    {
        foreach ($this->emitted as $metric) {
            if ($metric->name() !== $metricName) {
                continue;
            }

            foreach ($dimensions as $expected) {
                $actual = $metric->dimensions()->values()->get($expected->key());
                if ($actual !== $expected->value()) {
                    continue 2;
                }
            }

            return;
        }

        $message = "Metric '{$metricName}' with specified dimensions was not emitted";
        throw new \AssertionError($message);
    }
}
