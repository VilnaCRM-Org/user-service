<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\Source;

use App\Shared\Application\Resolver\Source\BatchEmailSource;
use App\Shared\Application\Resolver\Source\ChainEmailSource;
use App\Shared\Application\Resolver\Source\NullEmailSource;
use App\Tests\Unit\UnitTestCase;

final class EmailSourceChainTest extends UnitTestCase
{
    public function testNullEmailSourceAlwaysReturnsNull(): void
    {
        $source = new NullEmailSource();

        self::assertNull($source->extract(['email' => 'ignored@example.com']));
    }

    public function testChainEmailSourceReturnsValueFromFirstSource(): void
    {
        $primary = new class() implements BatchEmailSource {
            #[\Override]
            /**
             * @param array<string, string|null>|bool|float|int|object|string|null $entry
             */
            public function extract($entry): ?string
            {
                return 'primary@example.com';
            }
        };

        $fallback = new class() implements BatchEmailSource {
            #[\Override]
            /**
             * @param array<string, string|null>|bool|float|int|object|string|null $entry
             */
            public function extract($entry): ?string
            {
                return 'fallback@example.com';
            }
        };

        $source = new ChainEmailSource($primary, $fallback);

        self::assertSame('primary@example.com', $source->extract([]));
    }

    public function testChainEmailSourceFallsBackWhenPrimaryReturnsNull(): void
    {
        $primary = new class() implements BatchEmailSource {
            #[\Override]
            /**
             * @param array<string, string|null>|bool|float|int|object|string|null $entry
             */
            public function extract($entry): ?string
            {
                return null;
            }
        };

        $fallback = new class() implements BatchEmailSource {
            #[\Override]
            /**
             * @param array<string, string|null>|bool|float|int|object|string|null $entry
             */
            public function extract($entry): ?string
            {
                return 'fallback@example.com';
            }
        };

        $source = new ChainEmailSource($primary, $fallback);

        self::assertSame('fallback@example.com', $source->extract([]));
    }
}
