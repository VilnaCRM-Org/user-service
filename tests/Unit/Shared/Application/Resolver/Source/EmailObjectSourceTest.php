<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\Source;

use App\Shared\Application\Resolver\Source\ObjectMethodEmailSource;
use App\Shared\Application\Resolver\Source\ObjectPropertyEmailSource;
use App\Tests\Unit\UnitTestCase;
use stdClass;

final class EmailObjectSourceTest extends UnitTestCase
{
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

    public function testObjectMethodSourceReturnsNullWhenMethodMissing(): void
    {
        $source = new ObjectMethodEmailSource('getEmail');

        self::assertNull($source->extract(new stdClass()));
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
            public function getEmail(): int
            {
                return 42;
            }
        };

        $source = new ObjectMethodEmailSource('getEmail');

        self::assertNull($source->extract($entry));
    }
}
