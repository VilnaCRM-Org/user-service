<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime;

use App\Shared\Infrastructure\Runtime\FrankenPhpRequestHandlerInvoker;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FrankenPhpRequestHandlerInvokerTest extends TestCase
{
    private const MISSING_HANDLE_REQUEST_MESSAGE = <<<'MESSAGE'
frankenphp_handle_request() is unavailable. Ensure FrankenPHP worker runtime is enabled.
MESSAGE;

    /**
     * @param list<string> $expectedCheckedFunctions
     * @param list<string> $expectedCalledFunctions
     */
    #[DataProvider('handleRequestVariants')]
    public function testInvokeDispatchesAvailableHandleRequestFunction(
        string $availableFunction,
        bool $expectedResult,
        array $expectedCheckedFunctions,
        array $expectedCalledFunctions,
    ): void {
        $checkedFunctions = [];
        $calledFunctions = [];
        $handlerCalls = 0;

        $invoker = $this->createInvoker(
            $checkedFunctions,
            $calledFunctions,
            $availableFunction,
            $expectedResult
                ? $this->createTruthyFunctionCaller($calledFunctions)
                : $this->createFalseyFunctionCaller($calledFunctions),
        );

        self::assertSame($expectedResult, $invoker->invoke(self::createHandler($handlerCalls)));
        self::assertSame($expectedCheckedFunctions, $checkedFunctions);
        self::assertSame($expectedCalledFunctions, $calledFunctions);
        self::assertSame(1, $handlerCalls);
    }

    public function testInvokeThrowsWhenNoFrankenPhpHandleRequestFunctionIsAvailable(): void
    {
        $checkedFunctions = [];
        $calledFunctions = [];
        $handlerCalls = 0;
        $invoker = $this->createInvoker($checkedFunctions, $calledFunctions, null);
        $exception = $this->captureRuntimeException(
            static fn () => $invoker->invoke(self::createHandler($handlerCalls)),
        );

        self::assertSame(self::MISSING_HANDLE_REQUEST_MESSAGE, $exception->getMessage());
        self::assertSame(
            [self::namespacedHandleRequest(), 'frankenphp_handle_request'],
            $checkedFunctions,
        );
        self::assertSame([], $calledFunctions);
        self::assertSame(0, $handlerCalls);
    }

    /**
     * @return iterable<string, array{0: string, 1: bool, 2: list<string>, 3: list<string>}>
     */
    public static function handleRequestVariants(): iterable
    {
        yield 'prefers namespaced function' => [
            self::namespacedHandleRequest(),
            true,
            [self::namespacedHandleRequest()],
            [self::namespacedHandleRequest()],
        ];
        yield 'falls back to global function' => [
            'frankenphp_handle_request',
            false,
            [self::namespacedHandleRequest(), 'frankenphp_handle_request'],
            ['frankenphp_handle_request'],
        ];
    }

    /**
     * @param list<string> $checkedFunctions
     * @param list<string> $calledFunctions
     */
    private function createInvoker(
        array &$checkedFunctions,
        array &$calledFunctions,
        ?string $availableFunction,
        ?Closure $functionCaller = null,
    ): FrankenPhpRequestHandlerInvoker {
        $functionCaller ??= $this->createTruthyFunctionCaller($calledFunctions);

        return new FrankenPhpRequestHandlerInvoker(
            static function (string $function) use (&$checkedFunctions, $availableFunction): bool {
                $checkedFunctions[] = $function;

                return $function === $availableFunction;
            },
            $functionCaller,
        );
    }

    private function captureRuntimeException(Closure $callback): RuntimeException
    {
        try {
            $callback();
            self::fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $exception) {
            return $exception;
        }
    }

    private static function createHandler(int &$handlerCalls): Closure
    {
        return static function () use (&$handlerCalls): void {
            ++$handlerCalls;
        };
    }

    /**
     * @param list<string> $calledFunctions
     */
    private function createTruthyFunctionCaller(array &$calledFunctions): Closure
    {
        return static function (string $callable, Closure $handler) use (&$calledFunctions): bool {
            $calledFunctions[] = $callable;
            $handler();

            return true;
        };
    }

    /**
     * @param list<string> $calledFunctions
     */
    private function createFalseyFunctionCaller(array &$calledFunctions): Closure
    {
        return static function (string $callable, Closure $handler) use (&$calledFunctions): bool {
            $calledFunctions[] = $callable;
            $handler();

            return false;
        };
    }

    private static function namespacedHandleRequest(): string
    {
        return 'App\\Shared\\Infrastructure\\Runtime\\frankenphp_handle_request';
    }
}
