<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

final class FrankenPhpRunner implements RunnerInterface
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly int $loopMax,
    ) {
    }

    public function run(): int
    {
        ignore_user_abort(true);

        $server = array_filter(
            $_SERVER,
            static fn (string $key): bool => !str_starts_with($key, 'HTTP_'),
            ARRAY_FILTER_USE_KEY,
        );
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        $sfRequest = null;
        $sfResponse = null;
        $handler = function () use ($server, &$sfRequest, &$sfResponse): void {
            $_SERVER += $server;

            $sfRequest = Request::createFromGlobals();
            $sfResponse = $this->kernel->handle($sfRequest);

            $sfResponse->send();
        };

        $loops = 0;

        do {
            $handled = frankenphp_handle_request($handler);

            if ($this->kernel instanceof TerminableInterface && $sfRequest && $sfResponse) {
                $this->kernel->terminate($sfRequest, $sfResponse);
            }

            gc_collect_cycles();
            gc_mem_caches();
        } while ($handled && (-1 === $this->loopMax || ++$loops <= $this->loopMax));

        return 0;
    }
}
