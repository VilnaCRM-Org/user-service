<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Emitter;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactoryInterface;
use App\Shared\Infrastructure\Observability\Formatter\EmfLogFormatter;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use Psr\Log\LoggerInterface;

/**
 * AWS EMF (Embedded Metric Format) Business Metrics Emitter
 *
 * Emits business metrics in AWS EMF format via Symfony logger.
 * CloudWatch automatically extracts metrics from EMF-formatted logs.
 */
final readonly class AwsEmfBusinessMetricsEmitter implements BusinessMetricsEmitterInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EmfLogFormatter $emfLogFormatter,
        private EmfPayloadFactoryInterface $payloadFactory
    ) {
    }

    #[\Override]
    public function emit(BusinessMetric $metric): void
    {
        try {
            $payload = $this->payloadFactory->createFromMetric($metric);
            $this->writeEmfLog($payload);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to emit EMF metric', [
                'metric' => $metric->name(),
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);
        }
    }

    #[\Override]
    public function emitCollection(MetricCollection $metrics): void
    {
        if ($metrics->isEmpty()) {
            return;
        }

        try {
            $payload = $this->payloadFactory->createFromCollection($metrics);
            $this->writeEmfLog($payload);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to emit EMF metric collection', [
                'metrics_count' => count($metrics),
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);
        }
    }

    private function writeEmfLog(EmfPayload $payload): void
    {
        $formatted = $this->emfLogFormatter->format($payload);
        if ($formatted === '') {
            return;
        }

        $this->logger->info($formatted);
    }
}
