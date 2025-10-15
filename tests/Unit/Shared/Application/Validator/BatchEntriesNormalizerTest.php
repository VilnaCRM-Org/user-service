<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\BatchEntriesNormalizer;
use App\Shared\Application\Validator\BatchEntriesResult;
use App\Tests\Unit\UnitTestCase;
use ArrayIterator;

final class BatchEntriesNormalizerTest extends UnitTestCase
{
    private BatchEntriesNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new BatchEntriesNormalizer();
    }

    public function testReturnsNotIterableResult(): void
    {
        $result = $this->normalizer->normalize('not iterable');

        self::assertSame(BatchEntriesResult::STATE_NOT_ITERABLE, $result->state());
        self::assertSame([], $result->entries());
    }

    public function testReturnsEmptyResultForEmptyArray(): void
    {
        $result = $this->normalizer->normalize([]);

        self::assertSame(BatchEntriesResult::STATE_EMPTY, $result->state());
        self::assertSame([], $result->entries());
    }

    public function testReturnsValidResultForArray(): void
    {
        $entries = [
            3 => ['email' => 'first@example.com'],
            7 => ['email' => 'second@example.com'],
        ];

        $result = $this->normalizer->normalize($entries);

        self::assertSame(BatchEntriesResult::STATE_VALID, $result->state());
        self::assertSame(array_values($entries), $result->entries());
    }

    public function testTraversableInputIsNormalizedToArray(): void
    {
        $entries = new ArrayIterator([
            5 => ['email' => 'alpha@example.com'],
        ]);

        $result = $this->normalizer->normalize($entries);

        self::assertSame(BatchEntriesResult::STATE_VALID, $result->state());
        self::assertSame(
            [['email' => 'alpha@example.com']],
            $result->entries()
        );
    }
}
