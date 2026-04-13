<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Runtime\FrankenPhpSymfony\Runtime as BaseRuntime;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

final class FrankenPhpRuntime extends BaseRuntime
{
    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof HttpKernelInterface && ($_SERVER['FRANKENPHP_WORKER'] ?? false)) {
            return new FrankenPhpRunner($application, $this->options['frankenphp_loop_max']);
        }

        return parent::getRunner($application);
    }
}
