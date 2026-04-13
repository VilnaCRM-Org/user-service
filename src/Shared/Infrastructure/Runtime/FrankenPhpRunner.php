<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Closure;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

final class FrankenPhpRunner implements RunnerInterface
{
    private readonly FrankenPhpRequestFactory $requestFactory;

    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly int $loopMax,
        ?Closure $bodyParserChecker = null,
    ) {
        $this->requestFactory = new FrankenPhpRequestFactory($bodyParserChecker);
    }

    #[\Override]
    public function run(): int
    {
        ignore_user_abort(true);

        $bootstrapServer = FrankenPhpBootstrapServer::fromRequest(
            $this->requestFactory->createBaseRequest(),
        );
        $loopGate = new FrankenPhpLoopGate($this->loopMax);
        $handledRequest = null;
        $handler = $this->createHandler($bootstrapServer, $handledRequest);

        do {
            $handledRequest = null;
            $handled = $this->handleRequest($handler);
            $this->terminateRequest($handledRequest);
            $this->collectGarbage();
        } while ($loopGate->keepRunning($handled));

        return 0;
    }

    private function handleRequest(Closure $handler): bool
    {
        return frankenphp_handle_request($handler);
    }

    private function terminateRequest(?FrankenPhpHandledRequest $handledRequest): void
    {
        if (
            $this->kernel instanceof TerminableInterface
            && $handledRequest instanceof FrankenPhpHandledRequest
        ) {
            $this->kernel->terminate($handledRequest->request, $handledRequest->response);
        }
    }

    private function collectGarbage(): void
    {
        gc_collect_cycles();
        gc_mem_caches();
    }

    private function createHandler(
        FrankenPhpBootstrapServer $bootstrapServer,
        ?FrankenPhpHandledRequest &$handledRequest,
    ): Closure {
        return function () use ($bootstrapServer, &$handledRequest): void {
            $request = $this->requestFactory->createFromGlobals();
            $bootstrapServer->mergeInto($request);
            $response = $this->kernel->handle($request);
            $response->send();
            $handledRequest = new FrankenPhpHandledRequest($request, $response);
        };
    }
}
