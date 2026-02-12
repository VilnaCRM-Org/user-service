<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Collector;

use App\Shared\Application\Collector\BatchEmailCollection;
use App\Shared\Application\Collector\BatchEmailCollector;
use App\Shared\Application\Resolver\BatchEmailResolver;
use App\Tests\Unit\UnitTestCase;
use ArrayIterator;
use ReflectionClass;

final class BatchEmailCollectorTest extends UnitTestCase
{
    private BatchEmailCollector $collector;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = new BatchEmailCollector(new BatchEmailResolver());
    }

    public function testCollectsEmailsAndDetectsMissing(): void
    {
        $entries = [
            ['email' => 'first@example.com'],
            ['name' => 'missing email'],
            ['email' => 'second@example.com'],
            ['email' => 'SECOND@example.com'],
        ];

        $collection = $this->collector->collect($entries);

        self::assertInstanceOf(BatchEmailCollection::class, $collection);
        self::assertTrue($collection->hasMissing());
        self::assertTrue($collection->hasDuplicates());
        self::assertSame(
            ['first@example.com', 'second@example.com', 'second@example.com'],
            $collection->emails()
        );
    }

    public function testCollectsWithoutMissing(): void
    {
        $entries = [
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
        ];

        $collection = $this->collector->collect($entries);

        self::assertFalse($collection->hasMissing());
        self::assertFalse($collection->hasDuplicates());
    }

    public function testCollectsFromTraversableEntries(): void
    {
        $entries = new ArrayIterator([
            ['email' => 'foo@example.com'],
            ['email' => 'bar@example.com'],
        ]);

        $collection = $this->collector->collect($entries);

        self::assertSame(
            ['foo@example.com', 'bar@example.com'],
            $collection->emails()
        );
    }

    public function testCollectsFromGeneratorEntries(): void
    {
        $entries = (/**
         * @psalm-return \Generator<'first'|'second', array{email: 'bar@example.com'|'foo@example.com'}, mixed, void>
         */
        static function (): \Generator {
            yield 'first' => ['email' => 'foo@example.com'];
            yield 'second' => ['email' => 'bar@example.com'];
        })();

        $collection = $this->collector->collect($entries);

        self::assertSame(
            ['foo@example.com', 'bar@example.com'],
            $collection->emails()
        );
    }

    public function testCollectsWithNonArrayEntries(): void
    {
        $entries = [
            ['email' => 'first@example.com'],
            '',
            ['email' => 'second@example.com'],
            123,
        ];

        $collection = $this->collector->collect($entries);

        self::assertInstanceOf(BatchEmailCollection::class, $collection);
        self::assertTrue($collection->hasMissing());
        self::assertSame(
            ['first@example.com', 'second@example.com'],
            $collection->emails()
        );
    }

    public function testToArrayPreservesArrayKeys(): void
    {
        $entries = [
            'first' => ['email' => 'foo@example.com'],
            'second' => ['email' => 'bar@example.com'],
        ];

        $result = $this->invokeToArray($entries);

        self::assertSame($entries, $result);
    }

    /**
     * @param iterable<array-key, array|object|string|int|float|bool|null> $entries
     *
     * @return array<array-key, array|object|string|int|float|bool|null>
     */
    private function invokeToArray(iterable $entries): array
    {
        $reflection = new ReflectionClass($this->collector);
        $method = $reflection->getMethod('toArray');
        $this->makeAccessible($method);

        $result = $method->invoke($this->collector, $entries);
        self::assertIsArray($result);

        return $result;
    }
}
