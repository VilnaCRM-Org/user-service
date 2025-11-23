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
     * @param array<int, array<int, array|bool|float|int|object|string|null>> $expectedCalls
     */
    protected function expectSequential(
        array $expectedCalls,
        callable|array|bool|float|int|object|string|null $returnValue = null
    ): callable {
        $callIndex = 0;
        $returnValues = is_array($returnValue) ? $returnValue : null;

        return function (...$args) use (&$callIndex, $expectedCalls, $returnValue, &$returnValues) {
            $this->assertLessThan(
                count($expectedCalls),
                $callIndex,
                'Too many calls received'
            );

            $expectedArgs = $expectedCalls[$callIndex];
            $this->assertGreaterThanOrEqual(
                count($expectedArgs),
                count($args)
            );

            foreach ($expectedArgs as $index => $expected) {
                $this->assertEquals($expected, $args[$index]);
            }

            $callIndex++;

            if (is_callable($returnValue)) {
                return $returnValue(...$args);
            }

            if ($returnValues !== null) {
                $this->assertNotEmpty(
                    $returnValues,
                    'No more return values available for sequential expectation'
                );

                return array_shift($returnValues);
            }

            return $returnValue;
        };
    }
}
