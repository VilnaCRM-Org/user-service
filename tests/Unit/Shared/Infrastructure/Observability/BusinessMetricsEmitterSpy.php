<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\Collection\MetricCollection;

final class BusinessMetricsEmitterSpy implements BusinessMetricsEmitterInterface
{
    /** @var array<int, BusinessMetric> */
    private array $emitted = [];
    private bool $shouldFail = false;

    #[\Override]
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

    #[\Override]
    public function emitCollection(MetricCollection $metrics): void
    {
        foreach ($metrics as $metric) {
            $this->emit($metric);
        }
    }

    public function count(): int
    {
        return count($this->emitted);
    }

    public function emitted(): MetricCollection
    {
        return new MetricCollection(...$this->emitted);
    }
}
