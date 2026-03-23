<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Extractor;

use App\Shared\Application\Resolver\Extractor\ArrayEmailExtractor;
use App\Tests\Unit\UnitTestCase;

final class EmailArrayExtractorTest extends UnitTestCase
{
    public function testArrayEmailExtractorReturnsNullForNonArrayEntries(): void
    {
        $source = new ArrayEmailExtractor();

        self::assertNull($source->extract('not an array'));
    }

    public function testArrayEmailExtractorExtractsEmailField(): void
    {
        $source = new ArrayEmailExtractor();

        self::assertSame(
            'array@example.com',
            $source->extract(['email' => 'array@example.com'])
        );
    }

    public function testArrayEmailExtractorReturnsNullWhenEmailNotString(): void
    {
        $source = new ArrayEmailExtractor();

        self::assertNull($source->extract(['email' => 123]));
    }
}
