<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Subscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Shared\Infrastructure\EventDispatcher\ResilientEventSubscriber;
use App\Shared\Infrastructure\Observability\Resolver\ApiEndpointMetricDimensionsResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Emits endpoint invocation metrics.
 *
 * IMPORTANT: Listener failures must not break the main HTTP response.
 * This is enforced by ResilientEventSubscriber with automatic error handling.
 */
final readonly class ApiEndpointBusinessMetricsSubscriber extends ResilientEventSubscriber
{
    public function __construct(
        LoggerInterface $logger,
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private ApiEndpointMetricDimensionsResolver $dimensionsResolver
    ) {
        parent::__construct($logger);
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->shouldEmitMetric($event)) {
            return;
        }

        $this->safeExecute(function () use ($event): void {
            $this->emitEndpointMetric($event);
        }, KernelEvents::RESPONSE);
    }

    private function shouldEmitMetric(ResponseEvent $event): bool
    {
        if (!$event->isMainRequest()) {
            return false;
        }

        $path = $event->getRequest()->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return false;
        }

        // Exclude health check endpoints from business metrics
        return !str_starts_with($path, '/api/health');
    }

    private function emitEndpointMetric(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $dimensions = $this->dimensionsResolver->dimensions($request);

        $this->metricsEmitter->emit(
            new EndpointInvocationsMetric(
                endpoint: $dimensions->endpoint(),
                operation: $dimensions->operation()
            )
        );
    }
}
