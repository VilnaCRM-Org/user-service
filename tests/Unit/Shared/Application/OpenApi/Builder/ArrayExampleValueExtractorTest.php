<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Extractor\ArrayExampleValueExtractor;
use App\Tests\Unit\UnitTestCase;

final class ArrayExampleValueExtractorTest extends UnitTestCase
{
    private ArrayExampleValueExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new ArrayExampleValueExtractor();
    }

    public function testReturnsNullWhenExampleIsNotArray(): void
    {
        $this->assertNull($this->extractor->extract('string'));
    }

    public function testReturnsNullWhenExampleIsEmptyArray(): void
    {
        $this->assertNull($this->extractor->extract([]));
    }

    public function testReturnsFirstListValue(): void
    {
        $this->assertSame('first', $this->extractor->extract(['first', 'second']));
    }

    public function testReturnsNullWhenListIsEmpty(): void
    {
        $result = $this->extractor->extract([]);
        $this->assertNull($result);
    }

    public function testReturnsFirstValueFromNumericIndexedArray(): void
    {
        $result = $this->extractor->extract([0 => 'zero', 1 => 'one', 2 => 'two']);
        $this->assertSame('zero', $result);
    }

    public function testReturnsFirstAssociativeValue(): void
    {
        $example = ['a' => ['nested' => true]];

        $this->assertSame($example['a'], $this->extractor->extract($example));
    }

    public function testListWithNullFirstElementReturnsNull(): void
    {
        $result = $this->extractor->extract([null, 'second', 'third']);
        $this->assertNull($result);
    }

    public function testAssociativeArrayWithNumericStringKeysUsesReset(): void
    {
        $example = ['1' => 'second', '0' => 'first', '2' => 'third'];
        $result = $this->extractor->extract($example);
        $this->assertSame('second', $result);
    }

    public function testEmptyArrayReturnsNullViaListCheck(): void
    {
        $emptyArray = [];
        $this->assertTrue(array_is_list($emptyArray));
        $result = $this->extractor->extract($emptyArray);
        $this->assertNull($result);
    }
}
