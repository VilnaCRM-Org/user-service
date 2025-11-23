<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Builder\ArrayExampleValueExtractor;
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

    public function testReturnsFirstAssociativeValue(): void
    {
        $example = ['a' => ['nested' => true]];

        $this->assertSame($example['a'], $this->extractor->extract($example));
    }
}
