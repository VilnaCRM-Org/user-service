<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\Source;

use App\Shared\Application\Resolver\Source\ArrayEmailSource;
use App\Tests\Unit\UnitTestCase;

final class EmailArraySourceTest extends UnitTestCase
{
    public function testArrayEmailSourceReturnsNullForNonArrayEntries(): void
    {
        $source = new ArrayEmailSource();

        self::assertNull($source->extract('not an array'));
    }

    public function testArrayEmailSourceExtractsEmailField(): void
    {
        $source = new ArrayEmailSource();

        self::assertSame(
            'array@example.com',
            $source->extract(['email' => 'array@example.com'])
        );
    }

    public function testArrayEmailSourceReturnsNullWhenEmailNotString(): void
    {
        $source = new ArrayEmailSource();

        self::assertNull($source->extract(['email' => 123]));
    }
}
