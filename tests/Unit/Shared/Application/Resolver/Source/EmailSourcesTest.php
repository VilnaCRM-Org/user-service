<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\Source;

use App\Shared\Application\Resolver\Source\ArrayEmailSource;
use App\Shared\Application\Resolver\Source\BatchEmailSource;
use App\Shared\Application\Resolver\Source\ChainEmailSource;
use App\Shared\Application\Resolver\Source\NullEmailSource;
use App\Shared\Application\Resolver\Source\ObjectMethodEmailSource;
use App\Shared\Application\Resolver\Source\ObjectPropertyEmailSource;
use App\Tests\Unit\UnitTestCase;

final class EmailSourcesTest extends UnitTestCase
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
            public function extract(mixed $entry): ?string
            {
                return 'primary@example.com';
            }
        };

        $fallback = new class() implements BatchEmailSource {
            #[\Override]
            public function extract(mixed $entry): ?string
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
            public function extract(mixed $entry): ?string
            {
                return null;
            }
        };

        $fallback = new class() implements BatchEmailSource {
            #[\Override]
            public function extract(mixed $entry): ?string
            {
                return 'fallback@example.com';
            }
        };

        $source = new ChainEmailSource($primary, $fallback);

        self::assertSame('fallback@example.com', $source->extract([]));
    }

    public function testObjectPropertySourceReturnsNullForNonObject(): void
    {
        $source = new ObjectPropertyEmailSource('email');

        self::assertNull($source->extract(['email' => 'value']));
    }

    public function testObjectPropertySourceExtractsPropertyValue(): void
    {
        $entry = new class() {
            public function __construct(public string $email = 'object@example.com')
            {
            }
        };

        $source = new ObjectPropertyEmailSource('email');

        self::assertSame('object@example.com', $source->extract($entry));
    }

    public function testObjectMethodSourceReturnsNullWhenMethodMissing(): void
    {
        $source = new ObjectMethodEmailSource('getEmail');

        self::assertNull($source->extract(new \stdClass()));
    }

    public function testObjectMethodSourceExtractsStringValue(): void
    {
        $entry = new class() {
            public function getEmail(): ?string
            {
                return 'method@example.com';
            }
        };

        $source = new ObjectMethodEmailSource('getEmail');

        self::assertSame('method@example.com', $source->extract($entry));
    }

    public function testObjectMethodSourceReturnsNullForNonStringValues(): void
    {
        $entry = new class() {
            public function getEmail(): mixed
            {
                return 42;
            }
        };

        $source = new ObjectMethodEmailSource('getEmail');

        self::assertNull($source->extract($entry));
    }

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

    public function testObjectPropertySourceReturnsNullForNonStringProperty(): void
    {
        $entry = new class() {
            public function __construct(public int $email = 123)
            {
            }
        };

        $source = new ObjectPropertyEmailSource('email');

        self::assertNull($source->extract($entry));
    }
}
