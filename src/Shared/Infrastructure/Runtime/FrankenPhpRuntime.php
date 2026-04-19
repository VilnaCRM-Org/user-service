<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Runtime\FrankenPhpSymfony\Runtime as BaseRuntime;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

final class FrankenPhpRuntime extends BaseRuntime
{
    #[\Override]
    public function getRunner(?object $application): RunnerInterface
    {
        if ($this->shouldUseWorkerRunner($application)) {
            return new FrankenPhpRunner($application, $this->loopMax());
        }

        return parent::getRunner($application);
    }

    private function shouldUseWorkerRunner(?object $application): bool
    {
        return $application instanceof HttpKernelInterface
            && $this->workerModeEnabled();
    }

    private function loopMax(): int
    {
        return isset($this->options['frankenphp_loop_max'])
            ? (int) $this->options['frankenphp_loop_max']
            : -1;
    }

    private function workerModeEnabled(): bool
    {
        $workerFlag = getenv('FRANKENPHP_WORKER');

        return $workerFlag !== false && $workerFlag !== '';
    }
}
