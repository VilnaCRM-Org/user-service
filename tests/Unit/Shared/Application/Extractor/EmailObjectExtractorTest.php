<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Extractor;

use App\Shared\Application\Resolver\Extractor\ObjectMethodEmailExtractor;
use App\Shared\Application\Resolver\Extractor\ObjectPropertyEmailExtractor;
use App\Tests\Unit\UnitTestCase;
use stdClass;

final class EmailObjectExtractorTest extends UnitTestCase
{
    public function testObjectPropertySourceReturnsNullForNonObject(): void
    {
        $source = new ObjectPropertyEmailExtractor('email');

        self::assertNull($source->extract(['email' => 'value']));
    }

    public function testObjectPropertySourceExtractsPropertyValue(): void
    {
        $email = $this->faker->email();
        $entry = new class($email) {
            public function __construct(public string $email)
            {
            }
        };

        $source = new ObjectPropertyEmailExtractor('email');

        self::assertSame($email, $source->extract($entry));
    }

    public function testObjectPropertySourceReturnsNullForNonStringProperty(): void
    {
        $email = $this->faker->numberBetween(1, 1000);
        $entry = new class($email) {
            public function __construct(public int $email)
            {
            }
        };

        $source = new ObjectPropertyEmailExtractor('email');

        self::assertNull($source->extract($entry));
    }

    public function testObjectMethodSourceReturnsNullWhenMethodMissing(): void
    {
        $source = new ObjectMethodEmailExtractor('getEmail');

        self::assertNull($source->extract(new stdClass()));
    }

    public function testObjectMethodSourceExtractsStringValue(): void
    {
        $email = $this->faker->email();
        $entry = new class($email) {
            public function __construct(private string $email)
            {
            }

            public function getEmail(): string
            {
                return $this->email;
            }
        };

        $source = new ObjectMethodEmailExtractor('getEmail');

        self::assertSame($email, $source->extract($entry));
    }

    public function testObjectMethodSourceReturnsNullForNonStringValues(): void
    {
        $email = $this->faker->numberBetween(1, 1000);
        $entry = new class($email) {
            public function __construct(private int $email)
            {
            }

            public function getEmail(): int
            {
                return $this->email;
            }
        };

        $source = new ObjectMethodEmailExtractor('getEmail');

        self::assertNull($source->extract($entry));
    }
}
