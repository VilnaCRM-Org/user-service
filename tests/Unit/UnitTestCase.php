<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class UnitTestCase extends TestCase
{
    protected Generator $faker;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    protected function makeAccessible(
        ReflectionMethod|ReflectionProperty $reflection
    ): void {
        /** @psalm-suppress UnusedMethodCall */
        $reflection->setAccessible(true);
    }

    /**
     * Execute a callback while converting PHP warnings/notices into exceptions.
     */
    protected function withoutPhpWarnings(callable $callback): mixed
    {
        $previousHandler = $this->setupErrorHandler();

        try {
            return $callback();
        } finally {
            $this->restoreErrorHandler($previousHandler);
        }
    }

    /**
     * @param array<int, array<int, array|bool|float|int|object|string|null>> $expectedCalls
     */
    protected function expectSequential(
        array $expectedCalls,
        callable|array|bool|float|int|object|string|null $returnValue = null
    ): callable {
        $callIndex = 0;
        $returnValues = $this->prepareReturnValues($returnValue);

        return function (...$args) use (&$callIndex, $expectedCalls, $returnValue, &$returnValues) {
            $this->validateSequentialCall($callIndex, $expectedCalls, $args);
            $callIndex++;

            return $this->getSequentialReturnValue($returnValue, $returnValues, $args);
        };
    }

    /** @return array<int, array|bool|float|int|object|string|null>|null */
    private function prepareReturnValues(
        callable|array|bool|float|int|object|string|null $returnValue
    ): ?array {
        return is_array($returnValue) ? $returnValue : null;
    }

    private function setupErrorHandler(): ?callable
    {
        return set_error_handler($this->createErrorHandlerCallback());
    }

    private function createErrorHandlerCallback(): callable
    {
        return static function (
            int $severity,
            string $message,
            string $file,
            int $line
        ): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        };
    }

    private function restoreErrorHandler(?callable $previousHandler): void
    {
        $previousHandler ? set_error_handler($previousHandler) : restore_error_handler();
    }

    /**
     * @param array<int, array<int, array|bool|float|int|object|string|null>> $expectedCalls
     * @param array<int, array|bool|float|int|object|string|null> $args
     */
    private function validateSequentialCall(int $callIndex, array $expectedCalls, array $args): void
    {
        $this->assertLessThan(count($expectedCalls), $callIndex, 'Too many calls received');

        $expectedArgs = $expectedCalls[$callIndex];
        $this->assertGreaterThanOrEqual(count($expectedArgs), count($args));

        foreach ($expectedArgs as $index => $expected) {
            $this->assertEquals($expected, $args[$index]);
        }
    }

    /**
     * @param array<int, array|bool|float|int|object|string|null>|null $returnValues
     * @param array<int, array|bool|float|int|object|string|null> $args
     *
     * @return array<int, array|bool|float|int|object|string|null>|bool|float|int|object|string|null
     */
    private function getSequentialReturnValue(
        callable|array|bool|float|int|object|string|null $returnValue,
        ?array &$returnValues,
        array $args
    ): array|bool|float|int|object|string|null {
        if (is_callable($returnValue)) {
            return $this->executeCallableReturn($returnValue, $args);
        }

        return $this->extractArrayReturn($returnValues, $returnValue);
    }

    /**
     * @param array<int, array|bool|float|int|object|string|null> $args
     *
     * @return array<int, array|bool|float|int|object|string|null>
     *         |bool|float|int|object|string|null
     */
    private function executeCallableReturn(
        callable $returnValue,
        array $args
    ): array|bool|float|int|object|string|null {
        return $returnValue(...$args);
    }

    /**
     * @param array<int, array|bool|float|int|object|string|null>|null $returnValues
     *
     * @return array<int, array|bool|float|int|object|string|null>|bool|float|int|object|string|null
     */
    private function extractArrayReturn(
        ?array &$returnValues,
        array|bool|float|int|object|string|null $defaultValue
    ): array|bool|float|int|object|string|null {
        if ($returnValues === null) {
            return $defaultValue;
        }

        $this->assertNotEmpty($returnValues, 'No more return values available');

        return array_shift($returnValues);
    }
}
