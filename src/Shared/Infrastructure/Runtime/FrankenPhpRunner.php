<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpBootstrapServerFactory;
use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpRequestFactory;
use Closure;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

final class FrankenPhpRunner implements RunnerInterface
{
    private readonly FrankenPhpBootstrapServerFactory $bootstrapServerFactory;
    private readonly FrankenPhpRequestFactory $requestFactory;
    private readonly FrankenPhpRequestHandlerInvoker $requestHandlerInvoker;

    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly int $loopMax,
        ?Closure $bodyParserChecker = null,
        ?FrankenPhpRequestHandlerInvoker $requestHandlerInvoker = null,
    ) {
        $this->bootstrapServerFactory = new FrankenPhpBootstrapServerFactory();
        $this->requestFactory = new FrankenPhpRequestFactory($bodyParserChecker);
        $this->requestHandlerInvoker = $requestHandlerInvoker
            ?? new FrankenPhpRequestHandlerInvoker();
    }

    #[\Override]
    public function run(): int
    {
        ignore_user_abort(true);

        $bootstrapServer = $this->bootstrapServerFactory->create(
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
        return $this->requestHandlerInvoker->invoke($handler);
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
