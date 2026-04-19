<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Closure;
use RuntimeException;

final class FrankenPhpRequestHandlerInvoker
{
    private const MISSING_HANDLE_REQUEST_MESSAGE = <<<'MESSAGE'
frankenphp_handle_request() is unavailable. Ensure FrankenPHP worker runtime is enabled.
MESSAGE;

    private readonly Closure $functionExists;
    private readonly Closure $functionCaller;

    public function __construct(
        ?Closure $functionExists = null,
        ?Closure $functionCaller = null,
    ) {
        $this->functionExists = $functionExists
            ?? static fn (string $function): bool => \function_exists($function);
        $this->functionCaller = $functionCaller
            ?? static fn (string $callable, Closure $handler): bool => \call_user_func(
                $callable,
                $handler,
            );
    }

    public function invoke(Closure $handler): bool
    {
        $namespacedHandleRequest = __NAMESPACE__ . '\frankenphp_handle_request';
        $functionExists = $this->functionExists;
        $functionCaller = $this->functionCaller;

        $handleRequest = match (true) {
            $functionExists($namespacedHandleRequest) => $namespacedHandleRequest,
            $functionExists('frankenphp_handle_request') => 'frankenphp_handle_request',
            default => throw new RuntimeException(self::MISSING_HANDLE_REQUEST_MESSAGE),
        };

        return $functionCaller($handleRequest, $handler);
    }
}
